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
        try {
            DB::table('gdpr_reports')
                ->where('id', $this->reportId)
                ->update(['status' => GdprReportStatus::PROCESSING->value]);

            $report = GdprReport::findOrFail($this->reportId);

            $user = User::with([
                'roles',
                'organizations.address',
                'organizations.sectors.sectorTranslations',
                'userConsents.consent',
                'student.university',
                'student.studyYear.studyYearTranslations',
                'student.studyProgram.studyProgramTranslations',
                'student.studyField.studyFieldTranslations',
            ])->findOrFail($report->user_id);

            $filename    = "gdpr_report_user_{$user->id}_" . now()->format('Y-m-d-His') . '.' . $this->format;
            $storagePath = 'gdpr_reports/' . $filename;

            Storage::disk('local')->makeDirectory('gdpr_reports');

            match ($this->format) {
                'pdf'  => $this->generatePdf($user, $storagePath),
                'xlsx' => $this->generateSpreadsheet($user, $storagePath, \Maatwebsite\Excel\Excel::XLSX),
                'csv'  => $this->generateSpreadsheet($user, $storagePath, \Maatwebsite\Excel\Excel::CSV),
            };

            $classificationId = SecurityClassification::where('name', 'confidential')->value('id');

            $document = Document::create([
                'owner_id'                   => $report->user_id,
                'security_classification_id' => $classificationId,
            ]);

            DocumentVersion::create([
                'document_id' => $document->id,
                'file_name'   => $filename,
                'file_path'   => $storagePath,
            ]);

            $report->update([
                'status'        => GdprReportStatus::COMPLETED->value,
                'attachment_id' => $document->id,
                'expires_at'    => now()->addMinutes(15),
            ]);

        } catch (Throwable $e) {
            DB::table('gdpr_reports')
                ->where('id', $this->reportId)
                ->update(['status' => GdprReportStatus::FAILED->value]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        DB::table('gdpr_reports')
            ->where('id', $this->reportId)
            ->update(['status' => GdprReportStatus::FAILED->value]);
    }

    private function generatePdf(User $user, string $storagePath): void
    {
        $pdf = Pdf::loadView('audit-compliance::gdpr.report-pdf', compact('user'))
            ->setPaper('a4', 'portrait');

        Storage::disk('local')->put($storagePath, $pdf->output());
    }

    private function generateSpreadsheet(User $user, string $storagePath, string $writerType): void
    {
        $content = Excel::raw(new GdprUserExport($user), $writerType);
        Storage::disk('local')->put($storagePath, $content);
    }
}
