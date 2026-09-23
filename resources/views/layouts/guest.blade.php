<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Paolo Paolo') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #090d16; }
        h1, h2, h3, .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-slate-100 bg-[#090d16]">
    <div class="w-full max-w-md">
        {{ $slot }}
    </div>
</body>
</html>