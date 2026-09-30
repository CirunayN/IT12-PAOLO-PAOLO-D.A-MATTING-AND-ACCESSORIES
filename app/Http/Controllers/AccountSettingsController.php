<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    /**
     * Display local account information.
     *
     * Email-change verification was intentionally removed. Password recovery
     * is handled by the offline approval/recovery-code flow.
     */
    public function show(Request $request): View
    {
        return view('settings.account', [
            'user' => $request->user(),
        ]);
    }
}
