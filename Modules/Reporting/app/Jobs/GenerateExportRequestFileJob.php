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

        if (! is_string($exportClass) || ! class_exists($exportClass)) {
            throw new \RuntimeException('Missing export class for queued Excel export.');
        }

        Excel::store(new $exportClass(), $path, $disk, $writerType);
    }

    protected function generatePdf(ExportRequest $exportRequest, string $disk, string $path): void
    {
        $meta = $exportRequest->meta ?? [];
        $modelClass = $meta['model_class'] ?? null;
        $modelId = $meta['model_id'] ?? null;
        $relations = $meta['relations'] ?? [];
        $view = $meta['view'] ?? null;
        $dataKey = $meta['data_key'] ?? 'item';
        $options = $meta['options'] ?? [];

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

        $model = $query->findOrFail($modelId);

        $pdfService = app(PdfService::class);
        Storage::disk($disk)->put($path, $pdfService->render($view, [$dataKey => $model], (array) $options));
    }
}