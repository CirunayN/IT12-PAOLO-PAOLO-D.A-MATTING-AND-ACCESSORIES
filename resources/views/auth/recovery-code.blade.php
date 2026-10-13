<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Save Recovery Code | Paolo Paolo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wadwad_paolo_logo.png') }}">
    @include('shared.offline-assets')
    <style>body{background:#080b11;font-family:Arial,sans-serif}.auth-card{background:rgba(14,18,28,.94);border:1px solid rgba(220,38,38,.3)}</style>
</head>
<body class="scenic-auth min-h-screen flex items-center justify-center p-4 text-slate-100">
<div class="w-full max-w-md auth-card rounded-3xl p-8 space-y-5">
    <h1 class="text-2xl text-center font-black">Password Updated</h1>
    <p class="text-sm text-center text-slate-400">Save your new recovery code, then sign in using your new password.</p>
    @include('shared.recovery-code-card', ['recovery' => $recovery])
    <a href="{{ route('login') }}" class="block text-center w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 font-bold">Continue to Login</a>
</div>
</body>
</html>
