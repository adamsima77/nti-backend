<?php

namespace Modules\Reporting\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Events\CallClosed;
use Modules\Reporting\Jobs\GenerateExportRequestFileJob;
use Modules\Reporting\Models\ExportRequest;

class HandleCallClosure implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CallClosed $event): void
    {
        $call = $event->call->load([
            'applications' => fn ($q) => $q->whereHas(
                'status', fn ($q) => $q->where('name', 'Schválené')
            ),
            'applications.projectKpis',
            'applications.projectOutputs',
        ]);

        $application = $call->applications->first();

        if (!$application) {
            Log::info("Call {$call->id} uzavretý bez schválenej prihlášky — report sa negeneruje.");
            return;
        }

        $adminId = User::role('admin')->value('id');

        if (!$adminId) {
            Log::warning("Call {$call->id}: nenašiel sa admin user pre generovanie reportu.");
            return;
        }

        $exportRequest = ExportRequest::create([
            'user_id'      => $adminId,
            'export_key'   => 'call_pdf',
            'kind'         => 'pdf',
            'format'       => 'pdf',
            'status'       => 'queued',
            'file_name'    => "project-report-{$call->id}.pdf",
            'storage_disk' => 'local',
            'queued_at'    => now(),
            'meta'         => [
                'model_class'  => \Modules\Programs\Models\Call::class,
                'model_id'     => $call->id,
                'model_relations' => [
                    'program:id,name',
                    'organization:id,name',
                    'currentStatusHistory.status:id,name',
                    'callCriteria:id,name',
                ],
                'view'         => 'programs::pdf.project-report',
                'data_key'     => 'call',
            ],
        ]);

        GenerateExportRequestFileJob::dispatch($exportRequest->id);

        Log::info("Call {$call->id} uzavretý — PDF report naplánovaný (ExportRequest #{$exportRequest->id}).");
    }
}
