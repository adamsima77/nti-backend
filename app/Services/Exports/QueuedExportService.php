<?php

namespace App\Services\Exports;

use Modules\Reporting\Jobs\GenerateExportRequestFileJob;
use Modules\Reporting\Models\ExportRequest;

class QueuedExportService
{
    public function queue(int $userId, string $exportKey, string $kind, string $format, string $fileName, array $meta): ExportRequest
    {
        $exportRequest = ExportRequest::query()->create([
            'user_id' => $userId,
            'export_key' => $exportKey,
            'kind' => $kind,
            'format' => $format,
            'status' => 'queued',
            'file_name' => $fileName,
            'storage_disk' => 'local',
            'queued_at' => now(),
            'meta' => $meta,
        ]);

        GenerateExportRequestFileJob::dispatch($exportRequest->id);

        return $exportRequest;
    }
}