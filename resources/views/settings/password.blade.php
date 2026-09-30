@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-gear text-red-500"></i>
                Settings
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Manage your account security settings.</p>
        </div>
    </div>

    @include('settings.partials.tabs', ['active' => 'password'])

    @if(auth()->user() && auth()->user()->isAdmin())
        <div class="glass-card rounded-2xl p-5 border border-amber-500/25">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-xl bg-amber-500/15 text-amber-500 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-life-ring"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-slate-900 dark:text-white">Administrator Recovery</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Manage your 10 offline recovery codes and employee password reset approvals in Security &amp; Access.
                        </p>
                    </div>
                </div>
                <a href="{{ route('security.index') }}"
                    class="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs whitespace-nowrap">
                    Open Security
                </a>
            </div>
        </div>
    @endif

    <div class="glass-card rounded-2xl p-6 sm:p-8 border shadow-lg">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-red-500/15 text-red-500 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fas fa-lock"></i>
            </div>
            <div>
                <h2 class="font-display font-black text-xl text-slate-900 dark:text-white">Change Password</h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Enter your current password before creating a new password.</p>
            </div>
        </div>

        <div class="mb-6 p-4 rounded-xl bg-slate-50 dark:bg-dark-900/70 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                <i class="fas fa-circle-info text-red-500"></i>
                Password Requirements
            </div>
            <ul class="text-xs text-slate-500 dark:text-slate-400 space-y-1 ml-5 list-disc">
                <li>Must contain 8 to 16 characters.</li>
                <li>Only uppercase letters A-Z, lowercase letters a-z, and numbers 0-9 are allowed.</li>
                <li>Spaces and special characters are not allowed.</li>
                <li>New password must be different from the current password.</li>
                @if(auth()->user() && auth()->user()->isAdmin())
                    <li>Changing the administrator password invalidates old recovery codes and generates a new set of 10.</li>
                @endif
            </ul>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Current Password <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" name="current_password" id="current_password" required autocomplete="current-password"
                           placeholder="Enter your current password"
                           class="w-full pl-11 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border {{ $errors->updatePassword->has('current_password') ? 'border-rose-500' : 'border-slate-300 dark:border-slate-700' }} text-sm font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
                @if($errors->updatePassword->has('current_password'))
                    <p class="mt-1.5 text-xs font-semibold text-rose-500"><i class="fas fa-circle-exclamation mr-1"></i>{{ $errors->updatePassword->first('current_password') }}</p>
                @endif
            </div>

            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    New Password <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" name="password" id="password" required minlength="8" maxlength="16" pattern="[A-Za-z0-9]{8,16}" autocomplete="new-password"
                           placeholder="Enter your new password"
                           class="w-full pl-11 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border {{ $errors->updatePassword->has('password') ? 'border-rose-500' : 'border-slate-300 dark:border-slate-700' }} text-sm font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
                @if($errors->updatePassword->has('password'))
                    <p class="mt-1.5 text-xs font-semibold text-rose-500"><i class="fas fa-circle-exclamation mr-1"></i>{{ $errors->updatePassword->first('password') }}</p>
                @endif
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Confirm New Password <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <i class="fas fa-shield-halved absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8" maxlength="16" pattern="[A-Za-z0-9]{8,16}" autocomplete="new-password"
                           placeholder="Re-enter your new password"
                           class="w-full pl-11 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
            </div>

            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30">
                <div class="flex items-start gap-2">
                    <i class="fas fa-triangle-exclamation text-amber-500 mt-0.5"></i>
                    <div>
                        <div class="text-xs font-bold text-amber-600 dark:text-amber-400">You will be logged out</div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            After changing your password, you must sign in again using the new password.
                            @if(auth()->user() && auth()->user()->isAdmin())
                                You will first be shown the new set of 10 recovery codes.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-black text-sm shadow-lg shadow-red-600/25 flex items-center gap-2">
                    <i class="fas fa-key"></i>
                    Change Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
