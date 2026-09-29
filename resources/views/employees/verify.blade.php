@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fas fa-envelope-circle-check text-red-500"></i>
                Verify Employee
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                The employee cannot log in until this verification is completed.
            </p>
        </div>

        <a href="{{ route('employees.index') }}"
            class="px-3.5 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs">
            &larr; Employees
        </a>
    </div>

    @if(session('status'))
    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-500 text-sm">
        {{ session('status') }}
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-500 text-sm">
        @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <div class="glass-card rounded-2xl p-6 border space-y-5">
        <div class="p-4 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm">
            <div>
                <span class="text-slate-400">Employee:</span>
                <strong>{{ $pending->name }}</strong>
            </div>

            <div>
                <span class="text-slate-400">Username:</span>
                <strong>{{ $pending->username }}</strong>
            </div>

            <div>
                <span class="text-slate-400">Email:</span>
                <strong>{{ $pending->email }}</strong>
            </div>

            <div class="mt-2">
                <span class="text-slate-400">Code status:</span>

                @if($pending->expires_at->isPast())
                <strong class="text-rose-500">
                    Expired
                </strong>
                @else
                <strong class="text-amber-500">
                    Valid until {{ $pending->expires_at->format('M d, Y h:i A') }}
                </strong>
                @endif
            </div>
        </div>

        @if($pending->expires_at->isPast())
        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-700 dark:text-amber-300">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            The old code has expired. Press <strong>Send New Code</strong>, then ask the employee for the new 6-digit code.
        </div>
        @endif

        <form method="POST"
            action="{{ route('employees.verify') }}"
            class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    6-Digit Confirmation Code
                </label>

                <input type="text"
                    name="code"
                    inputmode="numeric"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required
                    autofocus
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-center text-2xl tracking-[.5em] font-black">
            </div>

            <button type="submit"
                class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold">
                <i class="fas fa-user-check mr-1.5"></i>
                Verify &amp; Activate Employee Account
            </button>
        </form>

        <form method="POST"
            action="{{ route('employees.resend') }}">
            @csrf

            <button type="submit"
                class="w-full py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 font-bold text-xs">
                <i class="fas fa-paper-plane mr-1.5"></i>
                Send New Code
            </button>
        </form>

        <p class="text-[11px] text-slate-400 text-center">
            The newest code replaces the previous code and expires in 10 minutes.
        </p>
    </div>
</div>
@endsection
