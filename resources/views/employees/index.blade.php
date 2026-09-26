@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-users-gear text-red-500"></i>
                Employees
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Employee accounts are created only after Gmail confirmation.</p>
        </div>

        <a href="{{ route('employees.create') }}" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center gap-2">
            <i class="fas fa-user-plus"></i>
            Register Employee
        </a>
    </div>

    <div class="glass-card rounded-2xl border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Username</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-left">Email Verified</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="px-4 py-3 font-bold">{{ $employee->name }}</td>
                        <td class="px-4 py-3">{{ $employee->username }}</td>
                        <td class="px-4 py-3">{{ $employee->email }}</td>
                        <td class="px-4 py-3">{{ $employee->role }}</td>
                        <td class="px-4 py-3">
                            @if($employee->email_verified_at)
                                <span class="text-emerald-500 font-bold">Verified</span>
                            @else
                                <span class="text-amber-500 font-bold">Not Verified</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-slate-400">No employee accounts found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
