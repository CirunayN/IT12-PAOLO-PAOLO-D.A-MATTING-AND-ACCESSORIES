@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <div class="pb-3 border-b border-slate-200 dark:border-slate-800">
        <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-2.5">
            <i class="fas fa-envelope-circle-check text-red-500"></i>
            Verify New Email
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Your current email will not change until this code is verified.</p>
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
        <div class="p-4 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm space-y-1">
            <div><span class="text-slate-400">Account:</span> <strong>{{ $user->name }}</strong></div>
            <div><span class="text-slate-400">Current Email:</span> <strong>{{ $user->email }}</strong></div>
            <div><span class="text-slate-400">New Email:</span> <strong>{{ $pending->new_email }}</strong></div>
        </div>

        <form method="POST" action="{{ route('settings.email.verify') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">6-Digit Verification Code</label>
                <input type="text" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required autofocus
                       class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-center text-2xl tracking-[.5em] font-black">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold">
                Verify &amp; Change Email
            </button>
        </form>

        <form method="POST" action="{{ route('settings.email.resend') }}">
            @csrf
            <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 font-bold text-xs">
                Send New Code
            </button>
        </form>

        <form method="POST" action="{{ route('settings.email.cancel') }}">
            @csrf
            <button type="submit" class="w-full py-2.5 rounded-xl border border-rose-500/30 text-rose-500 font-bold text-xs hover:bg-rose-500/10">
                Cancel Email Change
            </button>
        </form>

        <p class="text-[11px] text-slate-400 text-center">The newest code expires in 10 minutes and replaces the previous code.</p>
    </div>
</div>
@endsection
