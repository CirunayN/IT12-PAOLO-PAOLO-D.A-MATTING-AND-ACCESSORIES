<div id="categoryManager" class="hidden fixed inset-0 z-[90] bg-black/70 backdrop-blur-sm items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="categoryManagerHeading"
     data-index-url="{{ route('categories.index') }}" data-store-url="{{ route('categories.store') }}">
    <section class="glass-card rounded-3xl w-full max-w-3xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden" tabindex="-1">
        <div class="flex items-center justify-between gap-3 p-5 sm:p-6 border-b border-slate-200 dark:border-slate-700">
            <h2 id="categoryManagerHeading" class="font-display font-bold text-xl"><i class="fas fa-tags text-red-500 mr-2" aria-hidden="true"></i>Manage Categories</h2>
            <button type="button" id="categoryManagerClose" aria-label="Close category manager" class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-dark-800 text-xl">&times;</button>
        </div>
        <div class="p-5 sm:p-6 overflow-y-auto space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-2">
                    <button type="button" id="categoryActiveTab" aria-pressed="true" class="px-4 py-2 rounded-xl font-bold text-sm">Active <span id="categoryActiveCount">0</span></button>
                    <button type="button" id="categoryArchivedTab" aria-pressed="false" class="px-4 py-2 rounded-xl font-bold text-sm">Archived <span id="categoryArchivedCount">0</span></button>
                </div>
                <button type="button" id="categoryManagerAdd" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm">+ Add Category</button>
            </div>
            <div class="flex items-center gap-2">
                <input id="categoryManagerSearch" type="search" aria-label="Search categories" placeholder="Search categories..." class="flex-1 min-w-0 rounded-xl px-3 py-2.5 border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 text-sm">
                <button type="button" id="categoryManagerRefresh" aria-label="Refresh categories" class="px-3 py-2.5 rounded-xl bg-slate-100 dark:bg-dark-800"><i class="fas fa-rotate" aria-hidden="true"></i></button>
            </div>
            <p id="categoryManagerError" class="hidden text-sm text-rose-600 dark:text-rose-400" role="alert"></p>
            <p id="categoryManagerStatus" class="hidden text-sm text-emerald-700 dark:text-emerald-400" role="status" aria-live="polite"></p>
            <form id="categoryManagerForm" class="hidden p-4 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 space-y-3">
                <h3 id="categoryManagerFormHeading" class="font-bold">Add Category</h3>
                <label for="categoryManagerName" class="block text-xs font-bold">Category Name</label>
                <input id="categoryManagerName" name="Name" required maxlength="100" class="w-full rounded-xl px-3 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-dark-850">
                <div class="flex justify-end gap-2">
                    <button type="button" id="categoryManagerCancelEdit" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-700 font-bold text-sm">Cancel</button>
                    <button type="submit" id="categoryManagerSave" class="px-4 py-2 rounded-xl bg-red-600 text-white font-bold text-sm">Save Category</button>
                </div>
            </form>
            <div id="categoryArchiveConfirmation" class="hidden p-4 rounded-2xl border border-amber-400/50 bg-amber-500/10 space-y-3">
                <p id="categoryArchiveQuestion" class="font-bold"></p>
                <p class="text-sm text-slate-600 dark:text-slate-300">Existing products and their history keep this category. It will be hidden from new product selections.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" id="categoryArchiveCancel" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-700 font-bold text-sm">Cancel</button>
                    <button type="button" id="categoryArchiveConfirm" class="px-4 py-2 rounded-xl bg-amber-600 text-white font-bold text-sm">Archive Category</button>
                </div>
            </div>
            <div id="categoryManagerList" role="list" aria-label="Categories" class="space-y-2" aria-busy="false"></div>
        </div>
    </section>
</div>
