<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    /**
     * Display local account information.
     *
     * Password recovery uses a saved, private recovery code for every role.
     */
    public function show(Request $request): View
    {
        return view('settings.account', [
            'user' => $request->user(),
        ]);
    }
}
