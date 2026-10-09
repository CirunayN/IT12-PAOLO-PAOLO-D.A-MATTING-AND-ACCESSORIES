<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PrintableReport
{
    public function respond(Request $request, string $view, array $data)
    {
        $request->validate(['output' => 'nullable|in:print,pdf']);
        $data['downloadPdf'] = $request->query('output') === 'pdf';
        if (! $data['downloadPdf']) {
            return view($view, $data);
        }
        $filename = str_replace('.', '-', $view).'-'.now()->timezone('Asia/Manila')->format('Y-m-d').'.pdf';

        return Pdf::loadView($view, $data)->setPaper('a4', $view === 'transactions.print' ? 'portrait' : 'landscape')
            ->setOptions(['isRemoteEnabled' => false, 'isJavascriptEnabled' => false, 'defaultFont' => 'DejaVu Sans', 'defaultMediaType' => 'print'])
            ->download($filename);
    }
}
