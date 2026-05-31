<?php

namespace Modules\AuditCompliance\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\AuditCompliance\Enums\GdprReportStatus;
use Modules\AuditCompliance\Models\GdprReport;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PruneExpiredGdprExports extends Command
{
    protected $signature = 'gdpr:prune-expired';
    protected $description = 'Permanently deletes physical files for expired GDPR exports while leaving all database records and foreign keys intact.';

    public function handle(): int
    {
        $this->info('Starting GDPR expired exports pruning...');
        $maxCycles = 1000; // Safety check
        $cycleCount = 0;

        while (true) {
            $cycleCount++;
            if ($cycleCount > $maxCycles) {
                $this->error('Safety brake triggered: Exceeded maximum execution cycles. Potential infinite loop detected.');
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

                try {
                    DB::beginTransaction();

                    if ($document && $document->versions) {
                        foreach ($document->versions as $version) {
                            if ($version->file_path && Storage::disk('local')->exists($version->file_path)) {
                                Storage::disk('local')->delete($version->file_path);
                            }

                        }
                    }


                    $report->update([
                        'status' => GdprReportStatus::EXPIRED->value
                    ]);

                    DB::commit();
                    $this->line("Successfully pruned storage file for Report ID: {$report->id}");

                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Failed to prune Report ID {$report->id}: " . $e->getMessage());
                }
            }
        }

        $this->info('GDPR expired exports pruning completed.');
        return Command::SUCCESS;
    }
}
