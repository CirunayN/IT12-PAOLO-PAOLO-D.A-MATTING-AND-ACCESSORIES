<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline Password Recovery | Paolo Paolo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wadwad_paolo_logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=Outfit:wght@500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body{background-color:#080b11;background-image:radial-gradient(circle at 15% 50%,rgba(220,38,38,.16) 0%,transparent 45%),radial-gradient(circle at 85% 30%,rgba(245,158,11,.10) 0%,transparent 50%);font-family:'Inter',sans-serif}
        .font-jdm{font-family:'Dela Gothic One','Outfit',sans-serif}.auth-card{background:rgba(14,18,28,.90);backdrop-filter:blur(25px);border:1px solid rgba(220,38,38,.25);box-shadow:0 25px 50px -12px rgba(0,0,0,.8)}
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-slate-100">
<div class="w-full max-w-md auth-card rounded-3xl p-8 sm:p-10 relative overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-red-600 via-amber-500 to-red-600"></div>

    <div class="text-center mb-7">
        <img src="{{ asset('images/wadwad_paolo_logo.png') }}" alt="Paolo Paolo" class="w-20 h-20 mx-auto object-contain mb-3">
        <h1 class="text-xl sm:text-2xl font-jdm tracking-wider text-white">OFFLINE RECOVERY</h1>
        <p class="text-xs text-slate-400 mt-2">No email or internet is required. Enter your system username to begin password recovery.</p>
    </div>

    <div class="mb-5 grid gap-2 text-[11px]">
        <div class="p-3 rounded-xl bg-slate-900/70 border border-slate-700 text-slate-300">
            <strong class="text-red-400">Employee:</strong> request a reset, then ask the administrator to approve it and give you the generated code.
        </div>
        <div class="p-3 rounded-xl bg-slate-900/70 border border-slate-700 text-slate-300">
            <strong class="text-amber-400">Administrator:</strong> use one of your saved 10 recovery codes.
        </div>
    </div>

    @if($errors->any())
        <div class="mb-5 p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 text-xs">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Username</label>
            <div class="relative">
                <i class="fas fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500"></i>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username"
                    class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-white text-sm focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500"
                    placeholder="Enter your username">
            </div>
        </div>

        <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 text-white font-bold text-sm">
            <i class="fas fa-shield-halved mr-2"></i> Continue Offline Recovery
        </button>
    </form>

    <div class="mt-5 text-center">
        <a href="{{ route('login') }}" class="text-xs font-bold text-red-400 hover:text-red-300">
            <i class="fas fa-arrow-left mr-1"></i> Back to Login
        </a>
    </div>
</div>
</body>
</html>
