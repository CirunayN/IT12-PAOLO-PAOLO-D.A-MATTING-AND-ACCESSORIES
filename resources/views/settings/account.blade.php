@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-gear text-red-500"></i>
                Settings
            </h1>
        </div>
    </div>

    @include('settings.partials.tabs', ['active' => 'account'])

    <div class="glass-card rounded-2xl p-6 sm:p-8 border shadow-lg space-y-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-red-500/15 text-red-500 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fas fa-address-card"></i>
            </div>
            <div>
                <h2 class="font-display font-black text-xl text-slate-900 dark:text-white">Account Information</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                <div class="text-[11px] uppercase font-bold tracking-wider text-slate-400">Email</div>
                <div class="font-bold mt-1 text-slate-900 dark:text-white break-all">{{ $user->email ?: 'Not set' }}</div>
                <div class="text-[10px] text-slate-400 mt-1">Stored account information only; it is not used for password recovery.</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 md:col-span-2">
                <div class="text-[11px] uppercase font-bold tracking-wider text-slate-400">System Access</div>
                <div class="mt-1 flex items-center gap-2">
                    @if(($user->is_active ?? true))
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-xs font-bold">
                            <i class="fas fa-circle-check"></i> Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-500/10 text-rose-500 border border-rose-500/20 text-xs font-bold">
                            <i class="fas fa-ban"></i> Disabled
                        </span>
                    @endif
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $user->isAdmin() ? 'Administrator account' : 'Employee account' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-slate-50 dark:bg-dark-900/70 border border-slate-200 dark:border-slate-700">
            <div class="flex items-start gap-3">
                <i class="fas fa-shield-halved text-red-500 mt-0.5"></i>
                <div>
                    <div class="text-xs font-bold text-slate-700 dark:text-slate-200">Offline Account Security</div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Use the Password tab to change a password while signed in. If a password is forgotten, use the offline recovery process from the login screen.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
