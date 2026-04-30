<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Programs\Exports\CallExport;
use Modules\Programs\Models\Call;

class ExportController extends Controller
{
    public function calls(Request $request, $format = 'xlsx')
    {
        $this->authorize('export', Call::class);

        $format = strtolower($format ?: 'xlsx');
        $fileName = 'calls.' . $format;
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        return Excel::download(new CallExport(), $fileName, $writerType);
    }
}
