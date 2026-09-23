<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Paolo Paolo | D.A Matting & Accessories' }}</title>
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

            <a href="{{ (auth()->user() && auth()->user()->isAdmin()) ? route('dashboard') : route('pos.index') }}" title="Home"
                class="flex items-center gap-3 group p-1 rounded-2xl hover:bg-slate-100 dark:hover:bg-dark-800/60 transition-all cursor-pointer focus:outline-none">
                <img src="{{ asset('images/wadwad_paolo_logo.png') }}" alt="Paolo Paolo Logo" class="w-12 h-12 sm:w-14 sm:h-14 object-contain drop-shadow-lg group-hover:scale-105 transition-transform flex-shrink-0">
                
                <div class="text-left hidden sm:block">
                    <div class="font-jdm text-slate-900 dark:text-white text-base sm:text-lg tracking-wider group-hover:text-red-500 transition-colors">
                        PAOLO PAOLA
                    </div>
                    <div class="text-[11px] font-black text-red-600 dark:text-red-400 tracking-wider uppercase -mt-0.5 flex items-center gap-1.5">
                        <span>D.A Matting &amp; Accessories</span>
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
                        {{ (auth()->user() && auth()->user()->isAdmin()) ? 'Owner (Admin)' : 'Employee (' . (auth()->user()->role ?? 'Cashier') . ')' }}
                    </span>
                </div>
                <form id="globalLogoutForm" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="button" onclick="openLogoutModal(event)" title="Log out" class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-rose-500 text-slate-600 dark:text-slate-300 hover:text-white border border-slate-300 dark:border-slate-700 flex items-center justify-center text-base transition-colors cursor-pointer">
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
            <a href="{{ (auth()->user() && auth()->user()->isAdmin()) ? route('dashboard') : route('pos.index') }}" class="flex items-center gap-3 group">
                <img src="{{ asset('images/wadwad_paolo_logo.png') }}" alt="Paolo Paolo" class="w-12 h-12 object-contain drop-shadow">
                <div>
                    <h3 class="font-jdm text-slate-900 dark:text-white text-base group-hover:text-red-500 transition-colors">PAOLO PAOLO</h3>
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

            @if(auth()->user() && auth()->user()->isAdmin())
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800' }}">
                <i class="fas fa-chart-pie w-6 text-center text-lg {{ request()->routeIs('dashboard') ? 'text-red-500' : 'text-slate-400' }}"></i>
                <span class="font-display">Executive Dashboard</span>
            </a>
            @endif

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
            @endif
        </nav>

        <!-- Drawer Footer / Logout -->
        <div class="p-4 sm:p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-dark-850/70">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{{ (auth()->user() && auth()->user()->isAdmin()) ? 'Owner (Admin)' : (auth()->user()->role ?? 'Cashier') }}</p>
                </div>
                <button type="button" onclick="closeDrawer(); openLogoutModal(event);" class="px-3.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white font-bold text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                    <i class="fas fa-power-off"></i>
                    <span>Log Out</span>
                </button>
            </div>
        </div>
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
            <span>&copy; {{ date('Y') }} <strong class="text-slate-700 dark:text-slate-200 font-jdm">Paolo Paolo</strong> &bull; D.A Matting &amp; Accessories.</span>
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

        // Custom Logout Modal & Cart Validation
        function openLogoutModal(e) {
            if (e) e.preventDefault();

            const warningBox = document.getElementById('logoutCartWarningBox');
            const standardMsg = document.getElementById('logoutStandardMessage');
            const countEl = document.getElementById('logoutCartCount');
            const confirmBtnText = document.getElementById('logoutConfirmBtnText');
            const iconWrap = document.getElementById('logoutModalIconWrap');
            const icon = document.getElementById('logoutModalIcon');

            // Custom POS validation: check if active cart items exist
            let cartCount = 0;
            if (typeof window.getPosCartItemCount === 'function') {
                cartCount = window.getPosCartItemCount();
            }

            if (cartCount > 0) {
                warningBox.classList.remove('hidden');
                standardMsg.classList.add('hidden');
                countEl.textContent = cartCount + (cartCount === 1 ? ' item' : ' items');
                confirmBtnText.textContent = 'Discard Cart & Log Out';
                iconWrap.className = 'w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-500 flex items-center justify-center text-xl flex-shrink-0';
                icon.className = 'fas fa-triangle-exclamation';
            } else {
                warningBox.classList.add('hidden');
                standardMsg.classList.remove('hidden');
                confirmBtnText.textContent = 'Yes, Log Out';
                iconWrap.className = 'w-12 h-12 rounded-2xl bg-amber-500/15 text-amber-500 flex items-center justify-center text-xl flex-shrink-0';
                icon.className = 'fas fa-arrow-right-from-bracket';
            }

            const modal = document.getElementById('logoutConfirmModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutConfirmModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function confirmLogoutAction() {
            const form = document.getElementById('globalLogoutForm');
            if (form) {
                form.submit();
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
            }
        });
    </script>

    <!-- CUSTOM LOGOUT CONFIRMATION MODAL -->
    <div id="logoutConfirmModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="glass-card rounded-2xl max-w-sm sm:max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-start gap-3.5">
                <div id="logoutModalIconWrap" class="w-12 h-12 rounded-2xl bg-amber-500/15 text-amber-500 flex items-center justify-center text-xl flex-shrink-0">
                    <i id="logoutModalIcon" class="fas fa-arrow-right-from-bracket"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-display font-black text-lg text-slate-900 dark:text-white leading-tight">Log Out Confirmation</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Ready to end your current session?</p>
                </div>
                <button type="button" onclick="closeLogoutModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl -mr-1 -mt-1 cursor-pointer">
                    &times;
                </button>
            </div>

            <!-- User Badge Box -->
            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-red-600 to-rose-500 text-white font-black text-sm flex items-center justify-center shadow-sm">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ auth()->user()->email ?? '' }} &bull; <strong class="text-red-500">{{ (auth()->user() && auth()->user()->isAdmin()) ? 'Owner (Admin)' : (auth()->user()->role ?? 'Cashier') }}</strong></p>
                </div>
            </div>

            <!-- Dynamic Cart Validation Warning (Visible only if POS cart has active items) -->
            <div id="logoutCartWarningBox" class="hidden p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 space-y-1.5 text-xs">
                <div class="flex items-center gap-2 font-bold text-rose-600 dark:text-rose-400 text-sm">
                    <i class="fas fa-triangle-exclamation text-base"></i>
                    <span>Active POS Cart Detected!</span>
                </div>
                <p id="logoutCartWarningMessage" class="text-xs leading-relaxed text-rose-800 dark:text-rose-200">
                    You currently have <strong id="logoutCartCount" class="underline font-black text-rose-600 dark:text-rose-300">0 items</strong> in your POS terminal cart. Logging out now will discard these cart items and reset the current sale.
                </p>
                <div class="pt-1 text-[11px] font-semibold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                    <i class="fas fa-info-circle"></i>
                    <span>Please charge or clear the transaction before signing out, or confirm exit below.</span>
                </div>
            </div>

            <!-- Standard Confirmation message if no cart items -->
            <p id="logoutStandardMessage" class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                You will be securely signed out of PAOLO PAOLO D.A Matting &amp; Accessories system. Any unsaved changes in progress will be lost.
            </p>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="closeLogoutModal()" class="flex-1 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm transition-colors cursor-pointer">
                    Stay Signed In
                </button>
                <button type="button" onclick="confirmLogoutAction()" class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-red-600/25 flex items-center justify-center gap-2 transition-all cursor-pointer">
                    <i class="fas fa-power-off"></i>
                    <span id="logoutConfirmBtnText">Yes, Log Out</span>
                </button>
            </div>
        </div>
    </div>
    @stack('scripts')
</body>
</html>