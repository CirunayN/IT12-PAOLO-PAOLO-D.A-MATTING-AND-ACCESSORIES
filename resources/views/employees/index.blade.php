@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-users-gear text-red-500"></i>
                Employees
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Only verified accounts can log in. Pending registrations can be verified later.
            </p>
        </div>

        <a href="{{ route('employees.create') }}"
            class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center gap-2">
            <i class="fas fa-user-plus"></i>
            Register Employee
        </a>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-500 text-sm font-bold">
        <i class="fas fa-circle-check mr-1.5"></i>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-500 text-sm">
        @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <!-- PENDING VERIFICATIONS -->
    <div class="glass-card rounded-2xl border overflow-hidden">
        <div class="px-4 sm:px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-user-clock text-amber-500"></i>
                    Pending Verification
                </h2>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    These employees have not completed code verification and cannot log in.
                </p>
            </div>

            <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-500 border border-amber-500/20 text-xs font-black">
                {{ $pendingEmployees->count() }} pending
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-dark-900/30">
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Username</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Code Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($pendingEmployees as $pending)
                    @php
                        $expired = $pending->expires_at->isPast();
                    @endphp

                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">
                            {{ $pending->name }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $pending->username }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $pending->email }}
                        </td>

                        <td class="px-4 py-3">
                            @if($expired)
                            <div>
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-rose-500/10 text-rose-500 border border-rose-500/20 text-xs font-bold">
                                    <i class="fas fa-clock"></i>
                                    Code Expired
                                </span>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    Open Verify and send a new code.
                                </div>
                            </div>
                            @else
                            <div>
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-500/10 text-amber-500 border border-amber-500/20 text-xs font-bold">
                                    <i class="fas fa-envelope"></i>
                                    Waiting for Code
                                </span>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    Expires {{ $pending->expires_at->diffForHumans() }}
                                </div>
                            </div>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('employees.pending.verify', $pending->id) }}"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-black shadow-sm transition-all">
                                <i class="fas fa-user-check"></i>
                                Verify
                            </a>
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="5"
                            class="px-4 py-8 text-center text-slate-400">
                            No pending employee verifications.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- REGISTERED EMPLOYEES -->
    <div class="glass-card rounded-2xl border overflow-hidden">
        <div class="px-4 sm:px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-address-card text-red-500"></i>
                Registered Employees
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Username</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-left">Login Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($employees as $employee)
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="px-4 py-3 font-bold">
                            {{ $employee->name }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $employee->username }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $employee->email }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $employee->role }}
                        </td>

                        <td class="px-4 py-3">
                            @if($employee->email_verified_at)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-xs font-bold">
                                <i class="fas fa-circle-check"></i>
                                Verified — Can Login
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-rose-500/10 text-rose-500 border border-rose-500/20 text-xs font-bold">
                                <i class="fas fa-ban"></i>
                                Unverified — Login Blocked
                            </span>
                            @endif
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="5"
                            class="px-4 py-10 text-center text-slate-400">
                            No employee accounts found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
