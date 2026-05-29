<?php

namespace Modules\AuditCompliance\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Applications\Models\Document;
use Modules\Applications\Models\DocumentVersion;
use Modules\Applications\Models\SecurityClassification;
use Modules\AuditCompliance\Enums\GdprReportStatus;
use Modules\AuditCompliance\Exports\GdprUserExport;
use Modules\AuditCompliance\Models\GdprReport;
use Modules\IdentityAccess\Models\User;
use Throwable;

class ProcessGdprExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        protected int $reportId,
        protected string $format,
    ) {}

    public function handle(): void
    {
        Log::info('[GDPR DEBUG] 1. Handle method entered.', ['report_id' => $this->reportId, 'format' => $this->format]);

        try {
            Log::info('[GDPR DEBUG] 2. Updating status to PROCESSING in database.');
            // Update status cleanly via direct query to avoid transaction locks
            DB::table('gdpr_reports')
                ->where('id', $this->reportId)
                ->update(['status' => GdprReportStatus::PROCESSING->value]);

            Log::info('[GDPR DEBUG] 3. Fetching GdprReport model.');
            $report = GdprReport::findOrFail($this->reportId);

            Log::info('[GDPR DEBUG] 4. Executing heavy User::with relationship query.', ['user_id' => $report->user_id]);
            $user = User::with([
                'roles',
                'organizations.address',
                'organizations.sectors.sectorTranslations',
                'userConsents.consent',
                'student.university',
                'student.studyYear.studyYearTranslations',       // Updated
                'student.studyProgram.studyProgramTranslations', // Updated
                'student.studyField.studyFieldTranslations',     // Updated
            ])->findOrFail($report->user_id);

            Log::info('[GDPR DEBUG] 5. User query finished. Building filename and paths.');
            $filename    = "gdpr_report_user_{$user->id}_" . now()->format('Y-m-d-His') . '.' . $this->format;
            $storagePath = 'gdpr_reports/' . $filename;

            Storage::disk('local')->makeDirectory('gdpr_reports');

            Log::info('[GDPR DEBUG] 6. Entering file generation match block.', ['format' => $this->format]);
            match ($this->format) {
                'pdf'  => $this->generatePdf($user, $storagePath),
                'xlsx' => $this->generateSpreadsheet($user, $storagePath, \Maatwebsite\Excel\Excel::XLSX),
                'csv'  => $this->generateSpreadsheet($user, $storagePath, \Maatwebsite\Excel\Excel::CSV),
            };

            Log::info('[GDPR DEBUG] 7. File successfully generated and stored. Resolving security classification.');
            $classificationId = SecurityClassification::where('name', 'confidential')->value('id');

            Log::info('[GDPR DEBUG] 8. Creating Document entry.');
            $document = Document::create([
                'owner_id'                   => $report->user_id,
                'security_classification_id' => $classificationId,
            ]);

            Log::info('[GDPR DEBUG] 9. Creating DocumentVersion entry.', ['document_id' => $document->id]);
            DocumentVersion::create([
                'document_id' => $document->id,
                'file_name'   => $filename,
                'file_path'   => $storagePath,
            ]);

            Log::info('[GDPR DEBUG] 10. Updating GdprReport status to COMPLETED.');
            $report->update([
                'status'        => GdprReportStatus::COMPLETED->value,
                'attachment_id' => $document->id,
                'expires_at'    => now()->addDays(30),
            ]);

            Log::info('[GDPR DEBUG] 11. Job finished completely and successfully.');
        } catch (Throwable $e) {
            Log::error('[GDPR DEBUG] ERROR ENCOUNTERED inside try block!', [
                'report_id' => $this->reportId,
                'format'    => $this->format,
                'error'     => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            // Immediately break the loop by changing DB status before letting queue engine manage retries
            DB::table('gdpr_reports')
                ->where('id', $this->reportId)
                ->update(['status' => GdprReportStatus::FAILED->value]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('[GDPR DEBUG] Job permanently failed execution threshold.', [
            'report_id' => $this->reportId,
            'error'     => $exception->getMessage()
        ]);

        DB::table('gdpr_reports')
            ->where('id', $this->reportId)
            ->update(['status' => GdprReportStatus::FAILED->value]);

        Log::error('GDPR export permanently failed after all retries', [
            'report_id' => $this->reportId,
            'format'    => $this->format,
            'error'     => $exception->getMessage(),
        ]);
    }

    private function generatePdf(User $user, string $storagePath): void
    {
        Log::info('[GDPR DEBUG] [PDF] Executing Barryvdh\DomPDF engine.');
        $pdf = Pdf::loadView('audit-compliance::gdpr.report-pdf', compact('user'))
            ->setPaper('a4', 'portrait');

        Log::info('[GDPR DEBUG] [PDF] Writing output content to storage disk.');
        Storage::disk('local')->put($storagePath, $pdf->output());
    }

    private function generateSpreadsheet(User $user, string $storagePath, string $writerType): void
    {
        Log::info('[GDPR DEBUG] [EXCEL] Executing Maatwebsite\Excel engine raw compile.', ['writer_type' => $writerType]);
        $content = Excel::raw(new GdprUserExport($user), $writerType);

        Log::info('[GDPR DEBUG] [EXCEL] Writing output content to storage disk.');
        Storage::disk('local')->put($storagePath, $content);
    }
}
