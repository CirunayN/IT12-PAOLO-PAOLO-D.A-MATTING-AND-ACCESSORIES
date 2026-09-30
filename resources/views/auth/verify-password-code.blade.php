<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Recovery Code | Paolo Paolo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wadwad_paolo_logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{background:#080b11;font-family:Arial,sans-serif}.auth-card{background:rgba(14,18,28,.94);border:1px solid rgba(220,38,38,.3)}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-slate-100">
<div class="w-full max-w-md auth-card rounded-3xl p-8 relative overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-red-600 via-amber-500 to-red-600"></div>

    <div class="text-center mb-6">
        <div class="w-14 h-14 mx-auto rounded-2xl {{ $mode === 'admin' ? 'bg-amber-500/15 text-amber-400' : 'bg-red-500/15 text-red-400' }} flex items-center justify-center text-xl mb-3">
            <i class="fas {{ $mode === 'admin' ? 'fa-key' : 'fa-user-shield' }}"></i>
        </div>
        <h1 class="text-2xl font-black">{{ $mode === 'admin' ? 'Admin Recovery Code' : 'Employee Reset Code' }}</h1>
        <p class="text-xs text-slate-400 mt-2">Account: <strong class="text-slate-200">{{ $user->username }}</strong></p>
    </div>

    @if(session('status'))
        <div class="mb-4 p-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs">{{ session('status') }}</div>
    @endif

    @if($mode === 'employee')
        @if($resetRequest && $resetRequest->approved_at && $resetRequest->expires_at && $resetRequest->expires_at->isFuture())
            <div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs text-emerald-300">
                <div class="font-bold"><i class="fas fa-circle-check mr-1"></i> Approved by administrator</div>
                <div class="mt-1">Code valid until {{ $resetRequest->expires_at->format('M d, Y h:i A') }}. It can only be used once.</div>
            </div>
        @else
            <div class="mb-4 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-300">
                <div class="font-bold"><i class="fas fa-clock mr-1"></i> Waiting for administrator approval</div>
                <div class="mt-1">The administrator must approve your request and personally give you the generated code. You may refresh this page after approval.</div>
            </div>
        @endif
    @else
        <div class="mb-4 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-300">
            Enter one unused code from the 10 recovery codes you saved previously. Each recovery code can only be used once.
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.code.verify') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold uppercase text-slate-400 mb-1.5">Recovery / Reset Code</label>
            <input type="text" name="code" maxlength="19" required autofocus autocomplete="off" spellcheck="false"
                pattern="[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}"
                class="w-full px-3 py-3 rounded-xl bg-slate-900 border border-slate-700 text-center text-lg tracking-wider font-black text-white focus:border-red-500"
                placeholder="xxxx-xxxx-xxxx-xxxx">
        </div>
        <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 font-bold">Verify Code</button>
    </form>

    <div class="grid grid-cols-2 gap-2 mt-3">
        <a href="{{ route('password.code.form') }}" class="text-center py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold">Refresh Status</a>
        <a href="{{ route('password.request') }}" class="text-center py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold">Start Again</a>
    </div>

    <p class="text-[11px] text-slate-500 text-center mt-4">Code format: xxxx-xxxx-xxxx-xxxx using a-z, A-Z, and 0-9.</p>
</div>
</body>
</html>
