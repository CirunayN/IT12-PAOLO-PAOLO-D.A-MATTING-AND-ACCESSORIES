<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Admin Recovery Codes | Paolo Paolo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wadwad_paolo_logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body{background:#080b11;font-family:Arial,sans-serif}.auth-card{background:rgba(14,18,28,.96);border:1px solid rgba(245,158,11,.35)}
        @media print{body{background:#fff!important;color:#000!important}.no-print{display:none!important}.auth-card{border:0!important;background:#fff!important;box-shadow:none!important}.code{color:#000!important;border-color:#bbb!important;background:#fff!important}}
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-slate-100">
<div class="w-full max-w-2xl auth-card rounded-3xl p-8 relative overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-amber-500 via-red-600 to-amber-500"></div>

    <div class="text-center mb-6">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-500/15 text-amber-400 flex items-center justify-center text-xl mb-3"><i class="fas fa-key"></i></div>
        <h1 class="text-2xl font-black">New Administrator Recovery Codes</h1>
        <p class="text-xs text-slate-400 mt-2">Your password changed, so every previous recovery code has been invalidated.</p>
    </div>

    <div class="mb-5 p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-200 text-xs leading-relaxed">
        <strong>Save these 10 codes now.</strong> Each code can be used once. The system stores only a secure hash, so these exact plaintext codes cannot be displayed again after you leave this page.
    </div>

    <div class="grid sm:grid-cols-2 gap-2 font-mono">
        @foreach($codes as $index => $code)
            <div class="code p-3 rounded-xl bg-slate-900 border border-slate-700 flex items-center gap-3">
                <span class="text-slate-500 text-xs w-5">{{ $index + 1 }}.</span>
                <strong class="tracking-wider text-sm sm:text-base">{{ $code }}</strong>
            </div>
        @endforeach
    </div>

    <div class="no-print flex flex-col sm:flex-row gap-3 mt-6">
        <button type="button" onclick="window.print()" class="flex-1 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 font-bold text-sm">
            <i class="fas fa-print mr-1"></i> Print / Save Codes
        </button>
        <a href="{{ route('login') }}" class="flex-1 py-3 rounded-xl bg-red-600 hover:bg-red-500 font-bold text-sm text-center">
            Continue to Login
        </a>
    </div>
</div>
</body>
</html>
