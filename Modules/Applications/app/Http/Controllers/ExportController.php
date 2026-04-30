<?php

namespace Modules\Applications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Applications\Exports\ApplicationExport;
use Modules\Applications\Models\Applications;

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
}
