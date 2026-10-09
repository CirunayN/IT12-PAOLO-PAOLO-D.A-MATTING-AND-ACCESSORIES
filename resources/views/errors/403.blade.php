<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden | PAOLO PAOLO D.A Matting &amp; Accessories</title>

    @include('shared.offline-assets')
    <style>
        :root {
            --font-sans: 'Plus Jakarta Sans';
            --font-display: 'Outfit';
            --font-jdm: 'Orbitron';
            --dark-950: 6 9 14;
            --dark-900: 10 15 24;
            --dark-850: 14 20 34;
            --dark-800: 20 28 46;
            --dark-700: 28 40 64;
        }
    </style>

</head>
<body class="bg-slate-100 dark:bg-dark-950 text-slate-800 dark:text-slate-100 min-h-screen flex items-center justify-center p-4 font-sans selection:bg-red-600 selection:text-white transition-colors duration-300">

    <div class="max-w-lg w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6 text-center">
        <!-- Logo -->
        <div class="flex items-center justify-center gap-3">
            <img src="{{ asset('images/wadwad_paolo_logo.png') }}" alt="Paolo Paolo" class="w-14 h-14 object-contain drop-shadow-md">
            <div class="text-left">
                <span class="font-jdm font-black text-lg text-slate-900 dark:text-white tracking-wider block">PAOLO PAOLO</span>
                <span class="text-[11px] font-bold text-red-500 uppercase tracking-widest block">D.A Matting &amp; Accessories</span>
            </div>
        </div>

        <!-- 403 Badge & Heading -->
        <div class="space-y-2">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-rose-500/15 text-rose-500 text-3xl mb-1">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div class="text-xs font-black uppercase tracking-widest text-rose-500">HTTP 403 &bull; Restricted Area</div>
            <h1 class="font-display font-black text-2xl sm:text-3xl text-slate-900 dark:text-white">
                Access Restricted
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed">
                {{ $exception->getMessage() ?: 'Your account role does not have permission to access this area. Financial analytics and database backups are reserved for Owner/Admin accounts.' }}
            </p>
        </div>

        @auth
        <!-- Current Account Pill -->
        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-dark-800/80 border border-slate-200 dark:border-slate-700/60 flex items-center justify-between text-left text-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-red-600 to-rose-500 text-white font-black text-xs flex items-center justify-center">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <strong class="text-slate-900 dark:text-white block">{{ auth()->user()->name }}</strong>
                    <span class="text-slate-400 text-[11px]">{{ auth()->user()->email }}</span>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/15 text-rose-500 border border-rose-500/20">
                {{ auth()->user()->role ?? 'Staff' }}
            </span>
        </div>
        @endauth

        <!-- Navigation Actions -->
        <div class="space-y-2.5 pt-2">
            <a href="{{ route('pos.index') }}" class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-black text-sm shadow-lg shadow-red-600/25 flex items-center justify-center gap-2 transition-transform transform hover:-translate-y-0.5">
                <i class="fas fa-cash-register"></i>
                <span>Go to POS Terminal</span>
            </a>

            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('products.index') }}" class="py-2.5 px-3 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors">
                    <i class="fas fa-boxes-stacked"></i>
                    <span>Inventory Catalog</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-rose-500 text-slate-700 dark:text-slate-200 hover:text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                        <span>Switch Account</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
