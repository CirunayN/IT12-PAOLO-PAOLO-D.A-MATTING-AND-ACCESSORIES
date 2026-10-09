<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | Paolo Paolo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wadwad_paolo_logo.png') }}">
    @include('shared.offline-assets')
    <style>body{background:#080b11;font-family:Arial,sans-serif}.auth-card{background:rgba(14,18,28,.94);border:1px solid rgba(220,38,38,.3)}</style>
</head>
<body class="scenic-auth min-h-screen flex items-center justify-center p-4 text-slate-100">
<div class="w-full max-w-md auth-card rounded-3xl p-8 relative overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-red-600 via-amber-500 to-red-600"></div>

    <div class="text-center mb-6">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-red-500/15 text-red-400 flex items-center justify-center text-xl mb-3"><i class="fas fa-key"></i></div>
        <h1 class="text-2xl font-black">Create New Password</h1>
        <p class="text-xs text-slate-400 mt-2">Verified account: <strong class="text-slate-200">{{ $user->username }}</strong></p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold uppercase text-slate-400 mb-1.5">New Password</label>
            <input type="password" name="password" required minlength="8" maxlength="16" pattern="[A-Za-z0-9]{8,16}" autocomplete="new-password"
                class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white focus:border-red-500"
                placeholder="8-16 letters and numbers">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-slate-400 mb-1.5">Confirm New Password</label>
            <input type="password" name="password_confirmation" required minlength="8" maxlength="16" pattern="[A-Za-z0-9]{8,16}" autocomplete="new-password"
                class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white focus:border-red-500"
                placeholder="Re-enter new password">
        </div>
        <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 font-bold">Reset Password</button>
    </form>
</div>
</body>
</html>
