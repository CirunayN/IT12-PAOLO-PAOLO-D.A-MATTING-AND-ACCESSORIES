<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | Paolo Paolo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wadwad_paolo_logo.png') }}">
    @include('shared.offline-assets')
    <style>
        body{background-color:#080b11;background-image:radial-gradient(circle at 15% 50%,rgba(220,38,38,.16) 0%,transparent 45%),radial-gradient(circle at 85% 30%,rgba(245,158,11,.10) 0%,transparent 50%);font-family:'Inter',sans-serif}
        .font-jdm{font-family:'Dela Gothic One','Outfit',sans-serif}.auth-card{background:rgba(14,18,28,.90);backdrop-filter:blur(25px);border:1px solid rgba(220,38,38,.25);box-shadow:0 25px 50px -12px rgba(0,0,0,.8)}
    </style>
</head>
<body class="scenic-auth min-h-screen flex items-center justify-center p-4 text-slate-100">
<div class="w-full max-w-md auth-card rounded-3xl p-8 sm:p-10 relative overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-red-600 via-amber-500 to-red-600"></div>

    <div class="text-center mb-7">
        <img src="{{ asset('images/wadwad_paolo_logo.png') }}" alt="Paolo Paolo" class="w-20 h-20 mx-auto object-contain mb-3">
        <h1 class="text-xl sm:text-2xl font-jdm tracking-wider text-white">PASSWORD RECOVERY</h1>
        <p class="text-xs text-slate-400 mt-2">Enter your username and the private recovery code you saved earlier. This works without internet for both Admin and Employee accounts.</p>
    </div>

    @if($errors->any())
        <div class="mb-5 p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 text-xs">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.recovery.verify') }}" class="space-y-4">
        @csrf
        <div>
            <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Username</label>
            <div class="relative">
                <i class="fas fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500"></i>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" maxlength="100"
                    class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-white text-sm focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500"
                    placeholder="Enter your username">
            </div>
        </div>

        <div>
            <label for="recovery_code" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Saved Recovery Code</label>
            <input type="text" id="recovery_code" name="recovery_code" required minlength="19" maxlength="19" autocomplete="off" spellcheck="false"
                pattern="[A-Za-z0-9]{4}(-[A-Za-z0-9]{4}){3}" placeholder="XXXX-XXXX-XXXX-XXXX"
                class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-white text-sm font-mono text-center focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
        </div>

        <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 text-white font-bold text-sm">
            <i class="fas fa-shield-halved mr-2"></i> Verify Recovery Code
        </button>
    </form>

    <p class="text-xs text-slate-400 mt-4">Generate and save your code from My Account &rarr; Password while signed in. A saved code stays valid until used or replaced. After verification, finish the reset within 10 minutes.</p>

    <div class="mt-5 text-center">
        <a href="{{ route('login') }}" class="text-xs font-bold text-red-400 hover:text-red-300">
            <i class="fas fa-arrow-left mr-1"></i> Back to Login
        </a>
    </div>
</div>
</body>
</html>
