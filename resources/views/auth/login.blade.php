<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Paolo Paolo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wadwad_paolo_logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=Outfit:wght@500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                        jdm: ['Dela Gothic One', 'Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                        },
                        amber: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #080b11;
            background-image:
                radial-gradient(circle at 15% 50%, rgba(220, 38, 38, 0.16) 0%, transparent 45%),
                radial-gradient(circle at 85% 30%, rgba(245, 158, 11, 0.10) 0%, transparent 50%);
            font-family: 'Inter', sans-serif;
        }
        .font-jdm { font-family: 'Dela Gothic One', 'Outfit', sans-serif; }
        .login-card {
            background: rgba(14, 18, 28, 0.90);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(220, 38, 38, 0.25);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 30px -8px rgba(220, 38, 38, 0.2);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-slate-100">

    <div class="w-full max-w-md login-card rounded-3xl p-8 sm:p-10 relative overflow-hidden">
        <!-- Japanese Sunburst Red Top Trim -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-red-600 via-amber-500 to-red-600"></div>

        <!-- Logo & Brand Header -->
        <div class="text-center mb-8">
            <img src="{{ asset('images/wadwad_paolo_logo.png') }}" alt="Paolo Paolo" class="w-24 h-24 mx-auto object-contain drop-shadow-xl mb-3 hover:scale-105 transition-transform">
            <h1 class="text-2xl sm:text-3xl font-jdm tracking-wider text-white">
                PAOLO PAOLO
            </h1>
            <p class="text-xs font-bold text-red-500 uppercase tracking-widest mt-1">
                D.A Matting &amp; Accessories
            </p>
        </div>

        @if($errors->any())
        <div class="mb-5 p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 text-xs">
            <div class="font-bold flex items-center gap-1.5 mb-1">
                <i class="fas fa-circle-exclamation"></i>
                <span>Authentication Failed</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-[11px]">
                @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <!-- Username -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Username</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                    <input type="text" name="username" required autofocus
                        value="{{ old('username', 'admin') }}"
                        class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-colors"
                        placeholder="admin or cashier">
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                    <input type="password" name="password" required
                        class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-colors"
                        placeholder="••••••••">
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-bold font-display text-sm tracking-wide shadow-lg shadow-red-600/30 transition-all transform hover:-translate-y-0.5 cursor-pointer mt-2">
                <i class="fas fa-arrow-right-to-bracket mr-2"></i> Sign In to System
            </button>
        </form>

        <!-- Footer Contact Info -->
        <div class="mt-8 pt-5 border-t border-slate-800/80 text-center text-[11px] text-slate-500 space-y-1">
            <div>&copy; {{ date('Y') }} Paolo Paolo. All rights reserved.</div>
        </div>
    </div>

</body>
</html>
