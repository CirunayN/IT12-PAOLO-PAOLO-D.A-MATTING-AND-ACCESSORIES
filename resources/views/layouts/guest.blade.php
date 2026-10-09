<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Paolo Paolo') }}</title>
    @include('shared.offline-assets')
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #090d16; }
        h1, h2, h3, .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="scenic-auth min-h-screen flex items-center justify-center p-4 text-slate-100 bg-[#090d16]">
    <div class="w-full max-w-md">
        {{ $slot }}
    </div>
</body>
</html>
