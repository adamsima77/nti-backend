<?php

namespace Modules\Reporting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Pdf\PdfService;
use Modules\Reporting\Models\ExportRequest;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Support\Facades\Log;

class GenerateExportRequestFileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $exportRequestId)
    {
    }

    public function handle(): void
    {
        $exportRequest = ExportRequest::query()->findOrFail($this->exportRequestId);

        if ($exportRequest->status === 'completed' && $exportRequest->storage_path !== null) {
            return;
        }

        $exportRequest->forceFill([
            'status' => 'processing',
            'processed_at' => now(),
            'error_message' => null,
        ])->save();

        try {
            $this->generateFile($exportRequest);

            $exportRequest->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $throwable) {
            $exportRequest->forceFill([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $throwable->getMessage(),
            ])->save();

            throw $throwable;
        }
    }

    protected function generateFile(ExportRequest $exportRequest): void
    {
        $disk = $exportRequest->storage_disk ?: 'local';
        $baseName = pathinfo($exportRequest->file_name, PATHINFO_FILENAME);
        $path = sprintf(
            'exports/%s/%s-%s.%s',
            $exportRequest->user_id,
            Str::slug($baseName) ?: 'export',
            $exportRequest->id,
            $exportRequest->format
        );

        if ($exportRequest->kind === 'excel') {
            $this->generateExcel($exportRequest, $disk, $path);
        } elseif ($exportRequest->kind === 'pdf') {
            $this->generatePdf($exportRequest, $disk, $path);
        } else {
            throw new \RuntimeException('Unsupported export type: ' . $exportRequest->kind);
        }

        $exportRequest->forceFill([
            'storage_disk' => $disk,
            'storage_path' => $path,
        ])->save();
    }

    protected function generateExcel(ExportRequest $exportRequest, string $disk, string $path): void
    {
        $meta = $exportRequest->meta ?? [];
        $exportClass = $meta['export_class'] ?? null;
        $writerType = $meta['writer_type'] ?? ExcelWriter::XLSX;
        $exportArgs = $meta['export_args'] ?? [];

        if (! is_string($exportClass) || ! class_exists($exportClass)) {
            throw new \RuntimeException('Missing export class for queued Excel export.');
        }

        if (! is_array($exportArgs)) {
            $exportArgs = [$exportArgs];
        }

        $export = empty($exportArgs)
            ? new $exportClass()
            : new $exportClass(...$exportArgs);

        Excel::store($export, $path, $disk, $writerType);
    }

    protected function generatePdf(ExportRequest $exportRequest, string $disk, string $path): void
    {
        $meta = $exportRequest->meta ?? [];
        $view = $meta['view'] ?? null;
        $viewData = $meta['view_data'] ?? null;
        $options = $meta['options'] ?? [];

        if (is_string($view) && $view !== '' && $viewData !== null) {
            try {
                $this->generatePdfFromView($view, $viewData, $options, $disk, $path);
            } catch (Throwable $e) {
                Log::error('Queued PDF generation failed for export_request', [
                    'export_request_id' => $exportRequest->id,
                    'view' => $view,
                    'view_data_preview' => is_scalar($viewData) ? $viewData : (is_array($viewData) ? array_slice($viewData, 0, 10) : null),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $exportRequest->forceFill([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'error_message' => $e->getMessage(),
                ])->save();

                throw $e;
            }
            return;
        }

        $modelClass = $meta['model_class'] ?? null;
        $modelId = $meta['model_id'] ?? null;
        $relations = $meta['relations'] ?? [];
        $dataKey = $meta['data_key'] ?? 'item';

        if (! is_string($modelClass) || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new \RuntimeException('Missing model class for queued PDF export.');
        }

        if (! is_string($view) || $view === '') {
            throw new \RuntimeException('Missing view for queued PDF export.');
        }

        $query = $modelClass::query();

        if (is_array($relations) && $relations !== []) {
            $query->with($relations);
        }

        $model      = $query->findOrFail($modelId);
        $extraData  = $meta['extra_data'] ?? [];

        $pdfService = app(PdfService::class);
        Storage::disk($disk)->put($path, $pdfService->render($view, array_merge([$dataKey => $model], $extraData), (array) $options));
    }

    protected function generatePdfFromView(string $view, mixed $viewData, array $options, string $disk, string $path): void
    {
        if (! is_string($view) || $view === '') {
            throw new \RuntimeException('Missing view for queued PDF export.');
        }

        $pdfService = app(PdfService::class);
        $normalizedData = $this->normalizeViewData($viewData);

        Storage::disk($disk)->put($path, $pdfService->render($view, $normalizedData, $options));
    }

    protected function normalizeViewData(mixed $data, bool $preserveRoot = true): mixed
    {
        if (is_array($data)) {
            if ($preserveRoot) {
                return array_map(fn ($value) => $this->normalizeViewData($value, false), $data);
            }

            if ($this->isAssocArray($data)) {
                $normalized = new \stdClass();

                foreach ($data as $key => $value) {
                    $normalized->{$key} = $this->normalizeViewData($value, false);
                }

                return $normalized;
            }

            return array_map(fn ($value) => $this->normalizeViewData($value, false), $data);
        }

        if (is_object($data)) {
            $normalized = new \stdClass();

            foreach (get_object_vars($data) as $key => $value) {
                $normalized->{$key} = $this->normalizeViewData($value, false);
            }

            return $normalized;
        }

        return $data;
    }

    protected function isAssocArray(array $data): bool
    {
        if ($data === []) {
            return false;
        }

        return array_keys($data) !== range(0, count($data) - 1);
    }
}