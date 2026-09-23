<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Wad-Wad Paolo | D.A Matting & Accessories' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wadwad_paolo_logo.png') }}">

    <!-- Google Fonts: Dela Gothic One (Japanese JDM Display), Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=Outfit:wght@500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS with Dark & Light Mode -->
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
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                        amber: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        },
                        dark: {
                            900: '#080b11',
                            850: '#0d121c',
                            800: '#131926',
                            700: '#1d2538',
                            600: '#2d3a54',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        html.dark { color-scheme: dark; }
        body {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.25s ease, color 0.25s ease;
        }
        .font-jdm { font-family: 'Dela Gothic One', 'Outfit', sans-serif; }
        h1, h2, h3, h4, .font-display { font-family: 'Outfit', sans-serif; }
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #dc2626; }
        .glass-card { backdrop-filter: blur(12px); }
        .dark .glass-card {
            background: rgba(19, 25, 38, 0.85);
            border: 1px solid rgba(220, 38, 38, 0.22);
        }
        .dark .glass-card:hover { border-color: rgba(239, 68, 68, 0.45); }
        html:not(.dark) .glass-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px -2px rgba(220, 38, 38, 0.05);
        }
        html:not(.dark) .glass-card:hover { border-color: #dc2626; }

        /* JDM Red Top Racing Line Accent */
        .jdm-racing-border {
            border-top: 3px solid #dc2626;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen antialiased flex flex-col bg-slate-100 text-slate-800 dark:bg-[#080b11] dark:text-slate-100 text-base selection:bg-red-600 selection:text-white">

    <!-- TOP HEADER BAR -->
    <header class="h-20 bg-white/95 dark:bg-[#0d121c]/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800/80 sticky top-0 z-40 px-4 sm:px-8 flex items-center justify-between shadow-sm">
        
        <!-- Left: Menu Toggle + Brand Logo & Name -->
        <div class="flex items-center gap-3 sm:gap-4">
            <button type="button" id="menuToggleBtn" title="Open navigation menu"
                class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-800 dark:text-slate-100 border border-slate-300 dark:border-slate-700 font-bold text-sm shadow-sm transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-500/50">
                <i class="fas fa-bars text-base text-red-500"></i>
                <span class="font-display tracking-wide hidden xs:inline">Menu</span>
            </button>

            <a href="{{ route('dashboard') }}" title="Go to Dashboard"
                class="flex items-center gap-3 group p-1 rounded-2xl hover:bg-slate-100 dark:hover:bg-dark-800/60 transition-all cursor-pointer focus:outline-none">
                <img src="{{ asset('images/wadwad_paolo_logo.png') }}" alt="Wad-Wad Paolo Logo" class="w-12 h-12 sm:w-14 sm:h-14 object-contain drop-shadow-lg group-hover:scale-105 transition-transform flex-shrink-0">
                
                <div class="text-left hidden sm:block">
                    <div class="font-jdm text-slate-900 dark:text-white text-base sm:text-lg tracking-wider group-hover:text-red-500 transition-colors">
                        WAD-WAD PAOLO
                    </div>
                    <div class="text-[11px] font-black text-red-600 dark:text-red-400 tracking-wider uppercase -mt-0.5 flex items-center gap-1.5">
                        <span>D.A Matting &amp; Accessories</span>
                        <span class="text-amber-500 font-bold">&bull; JDM</span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Right: POS Shortcut, Theme Toggle, User Profile, Logout -->
        <div class="flex items-center gap-2.5 sm:gap-3.5">
            <a href="{{ route('pos.index') }}" class="flex items-center gap-2 px-4 sm:px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-red-600/25 transition-all transform hover:-translate-y-0.5">
                <i class="fas fa-cash-register text-base"></i>
                <span class="font-display">POS Terminal</span>
            </a>

            <!-- Light / Dark Mode Toggle -->
            <button type="button" id="themeToggleBtn" title="Toggle Light / Dark Mode"
                class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-amber-400 hover:bg-slate-300 dark:hover:bg-dark-700 border border-slate-300 dark:border-slate-700 flex items-center justify-center text-base sm:text-lg transition-colors cursor-pointer">
                <i id="themeIcon" class="fas fa-sun"></i>
            </button>

            <!-- User Info & Logout -->
            <div class="flex items-center gap-2 sm:gap-3 pl-2 border-l border-slate-300 dark:border-slate-800">
                <div class="hidden md:block text-right">
                    <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ auth()->user()->name ?? 'User' }}</div>
                    <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider {{ (auth()->user() && auth()->user()->isAdmin()) ? 'text-amber-500' : 'text-red-500' }}">
                        {{ auth()->user()->role ?? 'Staff' }}
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Are you sure you want to log out?');">
                    @csrf
                    <button type="submit" title="Log out" class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-rose-500 text-slate-600 dark:text-slate-300 hover:text-white border border-slate-300 dark:border-slate-700 flex items-center justify-center text-base transition-colors cursor-pointer">
                        <i class="fas fa-power-off"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- SLIDING NAVIGATION DRAWER -->
    <div id="navDrawerBackdrop" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden transition-opacity duration-300"></div>

    <aside id="navDrawer" class="fixed inset-y-0 left-0 z-50 w-80 sm:w-96 bg-white dark:bg-[#0d121c] border-r border-slate-200 dark:border-slate-800/80 shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
        <!-- Header -->
        <div class="p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-dark-850/50">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                <img src="{{ asset('images/wadwad_paolo_logo.png') }}" alt="Wad-Wad Paolo" class="w-12 h-12 object-contain drop-shadow">
                <div>
                    <h3 class="font-jdm text-slate-900 dark:text-white text-base group-hover:text-red-500 transition-colors">WAD-WAD PAOLO</h3>
                    <p class="text-[11px] font-bold text-red-500 uppercase">D.A Matting &amp; Accessories</p>
                </div>
            </a>
            <button type="button" id="closeDrawerBtn" class="w-9 h-9 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-dark-800 flex items-center justify-center text-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>



        <!-- Links -->
        <nav class="flex-1 p-5 space-y-2 overflow-y-auto">
            <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 px-3 pt-2 pb-1">Store Navigation</div>

            <a href="{{ route('dashboard') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800' }}">
                <i class="fas fa-chart-pie w-6 text-center text-lg {{ request()->routeIs('dashboard') ? 'text-red-500' : 'text-slate-400' }}"></i>
                <span class="font-display">Executive Dashboard</span>
            </a>

            <a href="{{ route('pos.index') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition-all {{ request()->routeIs('pos.*') ? 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800' }}">
                <i class="fas fa-cash-register w-6 text-center text-lg {{ request()->routeIs('pos.*') ? 'text-red-500' : 'text-slate-400' }}"></i>
                <span class="font-display">POS Terminal</span>
            </a>

            <a href="{{ route('products.index') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition-all {{ (request()->routeIs('products.*') || request()->routeIs('stock-in.*')) ? 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800' }}">
                <i class="fas fa-boxes-stacked w-6 text-center text-lg {{ (request()->routeIs('products.*') || request()->routeIs('stock-in.*')) ? 'text-red-500' : 'text-slate-400' }}"></i>
                <span class="font-display">Inventory &amp; Restock</span>
            </a>

            @if(auth()->user() && auth()->user()->isAdmin())
            <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 px-3 pt-4 pb-1">Admin Controls</div>

            <a href="{{ route('backup.index') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition-all {{ request()->routeIs('backup.*') ? 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800' }}">
                <i class="fas fa-hard-drive w-6 text-center text-lg {{ request()->routeIs('backup.*') ? 'text-red-500' : 'text-slate-400' }}"></i>
                <span class="font-display">Database Backup</span>
            </a>

            <a href="{{ route('users.index') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition-all {{ request()->routeIs('users.*') ? 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800' }}">
                <i class="fas fa-users-gear w-6 text-center text-lg {{ request()->routeIs('users.*') ? 'text-red-500' : 'text-slate-400' }}"></i>
                <span class="font-display">Staff &amp; Users</span>
            </a>
            @endif
        </nav>
    </aside>

    <!-- Flash Alerts -->
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-8 pt-4">
        @if(session('success'))
        <div class="p-4 mb-4 rounded-2xl bg-emerald-100 dark:bg-emerald-950/70 border border-emerald-300 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-md">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-emerald-600 dark:text-emerald-400 text-xl"></i>
                <span class="text-base font-medium">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400/60 hover:text-emerald-800 text-lg">&times;</button>
        </div>
        @endif

        @if(session('error'))
        <div class="p-4 mb-4 rounded-2xl bg-rose-100 dark:bg-rose-950/70 border border-rose-300 dark:border-rose-500/30 text-rose-800 dark:text-rose-300 flex items-center justify-between shadow-md">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-rose-600 dark:text-rose-400 text-xl"></i>
                <span class="text-base font-medium">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-600 dark:text-rose-400/60 hover:text-rose-800 text-lg">&times;</button>
        </div>
        @endif

        @if($errors->any())
        <div class="p-4 mb-4 rounded-2xl bg-amber-100 dark:bg-amber-950/70 border border-amber-300 dark:border-amber-500/30 text-amber-900 dark:text-amber-300 shadow-md">
            <div class="flex items-center gap-3 mb-1.5 font-bold text-base">
                <i class="fas fa-triangle-exclamation text-amber-600 dark:text-amber-400 text-xl"></i>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc list-inside text-sm space-y-1 pl-6">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>

    <!-- MAIN PAGE CONTENT -->
    <main class="flex-1 max-w-[1700px] w-full mx-auto px-4 sm:px-8 py-5">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="py-4 px-6 border-t border-slate-200 dark:border-slate-800/80 text-xs text-slate-500 dark:text-slate-400 text-center flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center justify-center gap-2">
            <img src="{{ asset('images/wadwad_paolo_logo.png') }}" class="w-5 h-5 object-contain inline-block">
            <span>&copy; {{ date('Y') }} <strong class="text-slate-700 dark:text-slate-200 font-jdm">Wad-Wad Paolo</strong> &bull; D.A Matting &amp; Accessories.</span>
        </div>
        <div class="text-[11px] text-slate-400">
            <span>Contacts: <strong>09267994701</strong> / <strong>09105508162</strong></span>
        </div>
    </footer>

    <!-- Global JavaScript -->
    <script>
        const themeBtn = document.getElementById('themeToggleBtn');
        const themeIcon = document.getElementById('themeIcon');

        function setTheme(isDark) {
            if (isDark) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('paolo_theme', 'dark');
                if (themeIcon) themeIcon.className = 'fas fa-sun text-amber-400';
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('paolo_theme', 'light');
                if (themeIcon) themeIcon.className = 'fas fa-moon text-slate-700';
            }
        }

        const savedTheme = localStorage.getItem('paolo_theme');
        if (savedTheme === 'light') {
            setTheme(false);
        } else {
            setTheme(true);
        }

        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                const isDark = document.documentElement.classList.contains('dark');
                setTheme(!isDark);
            });
        }

        const menuBtn = document.getElementById('menuToggleBtn');
        const drawer = document.getElementById('navDrawer');
        const backdrop = document.getElementById('navDrawerBackdrop');
        const closeBtn = document.getElementById('closeDrawerBtn');

        function openDrawer() {
            drawer.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
        }

        function closeDrawer() {
            drawer.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
        }

        if (menuBtn) menuBtn.addEventListener('click', openDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (backdrop) backdrop.addEventListener('click', closeDrawer);
    </script>
    @stack('scripts')
</body>
</html>