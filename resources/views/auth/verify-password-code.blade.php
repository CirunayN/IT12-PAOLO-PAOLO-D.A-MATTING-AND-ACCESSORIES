<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Code | Paolo Paolo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wadwad_paolo_logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{background:#080b11;font-family:Arial,sans-serif}.auth-card{background:rgba(14,18,28,.94);border:1px solid rgba(220,38,38,.3)}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-slate-100">
<div class="w-full max-w-md auth-card rounded-3xl p-8 relative overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-red-600 via-amber-500 to-red-600"></div>

    <div class="text-center mb-6">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-red-500/15 text-red-400 flex items-center justify-center text-xl mb-3"><i class="fas fa-shield-halved"></i></div>
        <h1 class="text-2xl font-black">Verify Gmail Code</h1>
        <p class="text-xs text-slate-400 mt-2">Code sent to <strong class="text-slate-200">{{ $email }}</strong></p>
    </div>

    @if(session('status'))
        <div class="mb-4 p-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.code.verify') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold uppercase text-slate-400 mb-1.5">6-Digit Code</label>
            <input type="text" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required autofocus
                class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-center text-2xl tracking-[.5em] font-black text-white focus:border-red-500"
                placeholder="000000">
        </div>
        <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 font-bold">Verify Code</button>
    </form>

    <form method="POST" action="{{ route('password.email') }}" class="mt-3">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold">Send New Code</button>
    </form>

    <p class="text-[11px] text-slate-500 text-center mt-4">The newest code replaces the previous code and expires after 10 minutes.</p>
</div>
</body>
</html>
