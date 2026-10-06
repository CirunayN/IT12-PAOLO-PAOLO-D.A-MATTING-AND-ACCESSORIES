<div class="mt-3 inline-flex items-center gap-1 p-1 rounded-xl bg-slate-100 dark:bg-dark-800 border border-slate-200 dark:border-slate-700">
    <a
        href="{{ route('dashboard') }}"
        class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all
            {{ (request()->routeIs('dashboard') && request('tab') !== 'reports')
                ? 'bg-white dark:bg-dark-700 text-red-600 dark:text-red-400 shadow-sm'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
    >
        <i class="fas fa-chart-pie mr-1.5"></i>
        Overview
    </a>

    <a
        href="{{ route('reports.index') }}"
        class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all
            {{ (request()->routeIs('reports.*') || request('tab') === 'reports')
                ? 'bg-white dark:bg-dark-700 text-red-600 dark:text-red-400 shadow-sm'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
    >
        <i class="fas fa-file-lines mr-1.5"></i>
        Printable Reports
    </a>
</div>
