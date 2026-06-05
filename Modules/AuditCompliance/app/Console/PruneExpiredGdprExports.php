<?php

namespace Modules\AuditCompliance\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\AuditCompliance\Enums\GdprReportStatus;
use Modules\AuditCompliance\Enums\EventType;
use Modules\AuditCompliance\Enums\SeverityType;
use Modules\AuditCompliance\Models\GdprReport;
use Modules\AuditCompliance\Models\SystemEvent;
use Throwable;

class PruneExpiredGdprExports extends Command
{
    protected $signature = 'gdpr:prune-expired';
    protected $description = 'Permanently deletes physical files for expired GDPR exports while leaving all database records and foreign keys intact.';

    public function handle(): int
    {
        $this->info('Starting GDPR expired exports pruning...');
        $maxCycles = 1000;
        $cycleCount = 0;

        $totalPrunedCount = 0;
        $purgedFileHistory = [];
        $failedPrunes = [];

        while (true) {
            $cycleCount++;
            if ($cycleCount > $maxCycles) {
                $this->error('Safety brake triggered: Exceeded maximum execution cycles. Potential infinite loop detected.');

                SystemEvent::create([
                    'event_type'  => EventType::SYSTEM_ERROR,
                    'severity'    => SeverityType::CRITICAL,
                    'message'     => 'GDPR Pruning Safety brake triggered: Exceeded maximum execution cycles.',
                    'stack_trace' => null,
                    'context'     => json_encode(['cycle_count' => $cycleCount, 'max_cycles' => $maxCycles]),
                    'user_id'     => null,
                    'ip_address'  => '127.0.0.1',
                    'created_at'  => now(),
                ]);

                return Command::FAILURE;
            }

            $reports = GdprReport::where('expires_at', '<', now())
                ->where('status', '!=', GdprReportStatus::EXPIRED->value)
                ->whereNotNull('attachment_id')
                ->with(['attachment.versions'])
                ->limit(100)
                ->get();

            if ($reports->isEmpty()) {
                break;
            }

            foreach ($reports as $report) {
                $document = $report->attachment;
                $deletedFilesForThisReport = [];

                try {
                    DB::beginTransaction();

                    if ($document && $document->versions) {
                        foreach ($document->versions as $version) {
                            if ($version->file_path && Storage::disk('local')->exists($version->file_path)) {
                                Storage::disk('local')->delete($version->file_path);
                                $deletedFilesForThisReport[] = $version->file_path;
                            }
                        }
                    }

                    $report->update([
                        'status' => GdprReportStatus::EXPIRED->value
                    ]);

                    DB::commit();

                    $totalPrunedCount++;
                    $purgedFileHistory[] = [
                        'report_id' => $report->id,
                        'user_id'   => $report->user_id ?? 'unknown',
                        'files'     => $deletedFilesForThisReport
                    ];

                    $this->line("Successfully pruned storage file for Report ID: {$report->id}");

                } catch (Throwable $e) {
                    DB::rollBack();

                    $failedPrunes[] = [
                        'report_id' => $report->id,
                        'error'     => $e->getMessage()
                    ];

                    $this->error("Failed to prune Report ID {$report->id}: " . $e->getMessage());
                }
            }
            unset($reports);
        }

        if ($totalPrunedCount > 0 || !empty($failedPrunes)) {
            $failedCount = count($failedPrunes);

            SystemEvent::create([
                'event_type'  => EventType::AUDIT,
                'severity'    => $failedCount > 0 ? SeverityType::ERROR : SeverityType::INFO,
                'message'     => "GDPR automated retention policy: {$totalPrunedCount} packages pruned"
                    . ($failedCount > 0 ? ", {$failedCount} failed" : ''),
                'stack_trace' => null,
                'context'     => [
                    'command'        => $this->signature,
                    'total_purged'   => $totalPrunedCount,
                    'purged_records' => $purgedFileHistory,
                    'failed_records' => $failedPrunes,
                ],
                'user_id'     => null,
                'ip_address'  => '127.0.0.1',
            ]);
        }

        //Checking if scheduler works correctly saving info to cache for 10 minutes
        Cache::put('scheduler:gdpr_retention:last_run', now()->toDateTimeString(), ttl: now()->addMinutes(10));
        $this->info('GDPR expired exports pruning completed.');
        return Command::SUCCESS;
    }
}
