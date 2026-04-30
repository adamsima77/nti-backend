<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Exports\UserExport;

class ExportController extends Controller
{
    public function users(Request $request, $format = 'xlsx')
    {
        $this->authorize('export', User::class);

        $format = strtolower($format ?: 'xlsx');
        $fileName = 'users.' . $format;

        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(new UserExport(), $fileName, $writerType);
    }
}
