<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class RecoveryCodeExportController extends Controller
{
    public function download(Request $request)
    {
        if (! $request->user()?->isAdmin()) {
            $allowedUntil = $request->session()->get('recovery_codes_export_until', 0);
            abort_unless($allowedUntil >= time(), 403);
        }
        $validated = $request->validate([
            'codes' => 'required|array|min:1|max:10',
            'codes.*' => 'required|string|max:40|regex:/^[A-Z0-9-]+$/',
        ]);

        return Pdf::loadView('security.recovery-codes-pdf', ['codes' => $validated['codes']])
            ->setPaper('a4')->setOptions(['isRemoteEnabled' => false, 'isJavascriptEnabled' => false])
            ->download('administrator-recovery-codes.pdf');
    }
}
