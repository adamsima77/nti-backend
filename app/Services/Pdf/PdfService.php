<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfService
{
    public function download(string $view, array $data = [], string $fileName = 'document.pdf', array $options = []): Response
    {
        $pdf = Pdf::loadView($view, $data);

        foreach ($options as $key => $value) {
            $pdf->setOption($key, $value);
        }

        return $pdf->download($fileName);
    }
}
