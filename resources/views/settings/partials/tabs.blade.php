<div class="glass-card rounded-2xl p-3 border">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('settings.account') }}"
           class="px-4 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 transition-colors {{ ($active ?? '') === 'account' ? 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800 border border-transparent' }}">
            <i class="fas fa-user-shield"></i>
            Account
        </a>

        <a href="{{ route('settings.password') }}"
           class="px-4 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 transition-colors {{ ($active ?? '') === 'password' ? 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800 border border-transparent' }}">
            <i class="fas fa-key"></i>
            Password
        </a>

        @if(auth()->user() && auth()->user()->isAdmin())
            <a href="{{ route('security.index') }}"
               class="px-4 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 transition-colors {{ ($active ?? '') === 'security' ? 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800 border border-transparent' }}">
                <i class="fas fa-shield-halved"></i>
                Security &amp; Access
            </a>
        @endif
    </div>
</div>
