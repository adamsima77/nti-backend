<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Response;

class PdfService
{
    protected function make(string $view, array $data = [], array $options = []): PDF
    {
        /** @var PDF $pdf */
        $pdf = app('dompdf.wrapper');
        $pdf->loadView($view, $data);

        foreach ($options as $key => $value) {
            $pdf->setOption($key, $value);
        }

        return $pdf;
    }

    public function download(string $view, array $data = [], string $fileName = 'document.pdf', array $options = []): Response
    {
        return $this->make($view, $data, $options)->download($fileName);
    }

    public function render(string $view, array $data = [], array $options = []): string
    {
        return $this->make($view, $data, $options)->output();
    }
}
