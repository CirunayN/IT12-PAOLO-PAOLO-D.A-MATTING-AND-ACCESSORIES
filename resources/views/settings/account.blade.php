@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-gear text-red-500"></i>
                Settings
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Manage your account and security settings.</p>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-3 border">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('settings.account') }}"
               class="px-4 py-2.5 rounded-xl bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30 font-bold text-sm flex items-center gap-2">
                <i class="fas fa-user-shield"></i>
                Account
            </a>

            <a href="{{ route('settings.password') }}"
               class="px-4 py-2.5 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800 font-bold text-sm flex items-center gap-2">
                <i class="fas fa-key"></i>
                Password
            </a>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-6 sm:p-8 border shadow-lg space-y-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-red-500/15 text-red-500 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fas fa-address-card"></i>
            </div>
            <div>
                <h2 class="font-display font-black text-xl text-slate-900 dark:text-white">Account Information</h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Your email is used for password recovery and account verification.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700">
                <div class="text-[11px] uppercase font-bold tracking-wider text-slate-400">Name</div>
                <div class="font-bold mt-1 text-slate-900 dark:text-white">{{ $user->name }}</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700">
                <div class="text-[11px] uppercase font-bold tracking-wider text-slate-400">Username</div>
                <div class="font-bold mt-1 text-slate-900 dark:text-white">{{ $user->username }}</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700">
                <div class="text-[11px] uppercase font-bold tracking-wider text-slate-400">Role</div>
                <div class="font-bold mt-1 text-slate-900 dark:text-white">{{ $user->isAdmin() ? 'Owner / Admin' : 'Employee' }}</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700">
                <div class="text-[11px] uppercase font-bold tracking-wider text-slate-400">Current Email</div>
                <div class="font-bold mt-1 text-slate-900 dark:text-white break-all">{{ $user->email }}</div>
            </div>
        </div>

        @if($pending)
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 space-y-3">
                <div class="flex items-start gap-2">
                    <i class="fas fa-envelope-circle-check text-amber-500 mt-0.5"></i>
                    <div class="flex-1">
                        <div class="text-sm font-bold text-amber-600 dark:text-amber-400">Pending Email Verification</div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                            A verification code was sent to <strong>{{ $pending->new_email }}</strong>.
                            Your current email has not changed yet.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('settings.email.verify.form') }}"
                       class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs">
                        Enter Verification Code
                    </a>

                    <form method="POST" action="{{ route('settings.email.cancel') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 font-bold text-xs">
                            Cancel Change
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
            <h3 class="font-display font-black text-lg text-slate-900 dark:text-white">Change Email Address</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Enter your current password, then a 6-digit code will be sent to the new email address.
            </p>
        </div>

        <form method="POST" action="{{ route('settings.email.send') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    New Email Address <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="email" name="email" id="email" required maxlength="255"
                           value="{{ old('email') }}"
                           placeholder="newemail@gmail.com"
                           class="w-full pl-11 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border {{ $errors->has('email') ? 'border-rose-500' : 'border-slate-300 dark:border-slate-700' }} text-sm font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs font-semibold text-rose-500"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Current Password <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" name="current_password" id="current_password" required autocomplete="current-password"
                           placeholder="Enter your current password"
                           class="w-full pl-11 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border {{ $errors->has('current_password') ? 'border-rose-500' : 'border-slate-300 dark:border-slate-700' }} text-sm font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
                @error('current_password')
                    <p class="mt-1.5 text-xs font-semibold text-rose-500"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>
                @enderror
            </div>

            <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/30 text-xs text-slate-600 dark:text-slate-300">
                <i class="fas fa-circle-info text-blue-500 mr-1"></i>
                The system must be online to send the Gmail verification code. Your old email remains active until the new email is verified.
            </div>

            <div class="flex justify-end pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-black text-sm shadow-lg shadow-red-600/25 flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i>
                    Send Verification Code
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
