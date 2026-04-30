<?php

namespace Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Pdf\PdfService;
use Modules\Reporting\Exports\ApplicationExport;
use Modules\Applications\Models\Applications;
use Modules\Reporting\Exports\UserExport;
use Modules\IdentityAccess\Models\User;
use Modules\Reporting\Exports\CallExport;
use Modules\Programs\Models\Call;
use Modules\Teams\Models\Team;

class ExportController extends Controller
{
    public function applications(Request $request, $format = 'xlsx')
    {
        $this->authorize('export', Applications::class);

        $format = strtolower($format ?: 'xlsx');
        $fileName = 'applications.' . $format;
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        return Excel::download(new ApplicationExport(), $fileName, $writerType);
    }

    public function users(Request $request, $format = 'xlsx')
    {
        $this->authorize('export', User::class);

        $format = strtolower($format ?: 'xlsx');
        $fileName = 'users.' . $format;
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        return Excel::download(new UserExport(), $fileName, $writerType);
    }

    public function calls(Request $request, $format = 'xlsx')
    {
        $this->authorize('export', Call::class);

        $format = strtolower($format ?: 'xlsx');
        $fileName = 'calls.' . $format;
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        return Excel::download(new CallExport(), $fileName, $writerType);
    }

    public function applicationPdf(Request $request, int $id, PdfService $pdfService)
    {
        $application = Applications::query()
            ->with([
                'call:id,name',
                'status:id,name',
                'documents:id',
                'statusHistory.status:id,name',
            ])
            ->where('created_by', $request->user()->id)
            ->findOrFail($id);

        return $pdfService->download(
            'applications::pdf.application-details',
            ['application' => $application],
            'application-' . $application->id . '.pdf'
        );
    }

    public function userPdf(User $user, PdfService $pdfService)
    {
        $this->authorize('pdf', $user);

        $user->load(['status', 'roles', 'teams']);

        return $pdfService->download(
            'identityaccess::pdf.profile',
            ['user' => $user],
            'user-profile-' . $user->id . '.pdf'
        );
    }

    public function callPdf(int $id, PdfService $pdfService)
    {
        $call = Call::query()
            ->with([
                'program:id,name',
                'organization:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria:id,name',
            ])
            ->whereHas('currentStatusHistory.status', function ($query) {
                $query->where('name', 'Publikované');
            })
            ->findOrFail($id);

        return $pdfService->download(
            'programs::pdf.project-report',
            ['call' => $call],
            'project-report-' . $call->id . '.pdf'
        );
    }

    public function teamPdf(Team $team, PdfService $pdfService)
    {
        $this->authorize('pdf', $team);

        $team->load('members');

        return $pdfService->download(
            'teams::pdf.team-report',
            ['team' => $team],
            'team-report-' . $team->id . '.pdf'
        );
    }
}
