@php
    $selectedCatId = request('category_id');
    $selectedCategory = $categories->firstWhere('ID', $selectedCatId);
    $selectedCategoryName = $selectedCategory ? $selectedCategory->Name : 'All Categories';
@endphp

<style>
    /* Category Filter Component - Dual Mode (Light & Dark) Styling */
    #invCategoryDropdownMenu {
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
        color: #0f172a !important;
    }
    html.dark #invCategoryDropdownMenu {
        background-color: #0e1422 !important;
        border: 1px solid rgba(220, 38, 38, 0.35) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 20px rgba(220, 38, 38, 0.15) !important;
        color: #f8fafc !important;
    }

    #invCategorySearchInput {
        background-color: #f1f5f9 !important;
        border: 1px solid #cbd5e1 !important;
        color: #0f172a !important;
        outline: none !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    #invCategorySearchInput::placeholder {
        color: #94a3b8 !important;
    }
    #invCategorySearchInput:focus {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
    }
    html.dark #invCategorySearchInput {
        background-color: #080b11 !important;
        border: 1px solid #334155 !important;
        color: #ffffff !important;
    }
    html.dark #invCategorySearchInput::placeholder {
        color: #64748b !important;
    }
    html.dark #invCategorySearchInput:focus {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.35) !important;
    }

    .inv-cat-opt {
        color: #334155 !important;
        background-color: transparent !important;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .inv-cat-opt:hover {
        background-color: #fef2f2 !important;
        color: #b91c1c !important;
    }
    .inv-cat-opt.is-selected {
        background-color: #fee2e2 !important;
        color: #dc2626 !important;
        font-weight: 700 !important;
    }

    html.dark .inv-cat-opt {
        color: #cbd5e1 !important;
        background-color: transparent !important;
    }
    html.dark .inv-cat-opt:hover {
        background-color: rgba(220, 38, 38, 0.16) !important;
        color: #ffffff !important;
    }
    html.dark .inv-cat-opt.is-selected {
        background-color: rgba(220, 38, 38, 0.25) !important;
        color: #f87171 !important;
        font-weight: 700 !important;
    }

    #invCategoryOptionsList::-webkit-scrollbar {
        width: 5px;
    }
    #invCategoryOptionsList::-webkit-scrollbar-track {
        background: transparent;
    }
    #invCategoryOptionsList::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }
    html.dark #invCategoryOptionsList::-webkit-scrollbar-thumb {
        background: #334155;
    }
    #invCategoryOptionsList::-webkit-scrollbar-thumb:hover {
        background: #ef4444;
    }

    #invCatEmptyMsg {
        color: #94a3b8;
    }
    html.dark #invCatEmptyMsg {
        color: #64748b;
    }
</style>

<div class="relative w-full z-40" id="invCategoryDropdownWrap">
    <!-- Hidden input carrying the category_id for form submission -->
    <input type="hidden" name="category_id" id="invCategoryIdInput" value="{{ $selectedCatId }}">

    <!-- Hidden legacy select so CategoryManager JS continues to work seamlessly without errors -->
    <select name="category_id_legacy" data-category-select="filter" class="hidden" style="display:none" aria-hidden="true" tabindex="-1">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
        <option value="{{ $cat->ID }}" {{ $selectedCatId == $cat->ID ? 'selected' : '' }}>{{ $cat->Name }}{{ $cat->Is_Archived ? ' (Archived)' : '' }}</option>
        @endforeach
    </select>

    <!-- Trigger Button -->
    <button type="button" id="invCategoryDropdownBtn" onclick="toggleInventoryCategoryDropdown(event)"
        class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-100 hover:border-red-500/60 shadow-sm transition-all cursor-pointer">
        <span class="flex items-center gap-2 min-w-0">
            <i class="fas fa-layer-group text-red-500 flex-shrink-0"></i>
            <span id="invCategorySelectedName" class="truncate">{{ $selectedCategoryName }}</span>
        </span>
        <i id="invCategoryChevron" class="fas fa-chevron-down text-slate-400 text-xs transition-transform duration-200 flex-shrink-0"></i>
    </button>

    <!-- Floating Searchable Menu -->
    <div id="invCategoryDropdownMenu"
        class="hidden absolute left-0 top-full mt-1.5 w-72 sm:w-80 max-w-[calc(100vw-2rem)] rounded-2xl shadow-2xl z-50 p-2.5 space-y-2">
        <!-- Category Search Box -->
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            <input type="text" id="invCategorySearchInput"
                placeholder="Search categories..."
                autocomplete="off"
                oninput="filterInventoryCategoryOptions(this.value)"
                onkeydown="if(event.key === 'Enter'){ event.preventDefault(); }"
                class="w-full pl-8 pr-3 py-1.5 rounded-lg text-xs placeholder-slate-400">
        </div>

        <!-- Category Options List -->
        <div id="invCategoryOptionsList" class="max-h-56 overflow-y-auto space-y-0.5 pr-1">
            <button type="button" onclick="selectInventoryCategory('', 'All Categories', event)"
                class="inv-cat-opt w-full text-left px-3 py-2 rounded-lg text-xs font-semibold flex items-center justify-between cursor-pointer {{ !$selectedCatId ? 'is-selected' : '' }}"
                data-id="" data-name="all categories">
                <span>All Categories</span>
                @if(!$selectedCatId)
                <i class="fas fa-check text-red-500 text-[10px]"></i>
                @endif
            </button>
            @foreach($categories as $cat)
            <button type="button" data-category-label="{{ $cat->Name }}" onclick="selectInventoryCategory('{{ $cat->ID }}', this.dataset.categoryLabel, event)"
                class="inv-cat-opt w-full text-left px-3 py-2 rounded-lg text-xs font-semibold flex items-center justify-between cursor-pointer {{ $selectedCatId == $cat->ID ? 'is-selected' : '' }}"
                data-id="{{ $cat->ID }}" data-name="{{ strtolower($cat->Name) }}">
                <span class="truncate">{{ $cat->Name }}{{ $cat->Is_Archived ? ' (Archived)' : '' }}</span>
                @if($selectedCatId == $cat->ID)
                <i class="fas fa-check text-red-500 text-[10px]"></i>
                @endif
            </button>
            @endforeach
            <p id="invCatEmptyMsg" class="hidden py-4 text-center text-xs">No categories match your search.</p>
        </div>
    </div>
</div>

<script>
if (typeof window.toggleInventoryCategoryDropdown !== 'function') {
    window.toggleInventoryCategoryDropdown = function (event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        const menu = document.getElementById('invCategoryDropdownMenu');
        const chevron = document.getElementById('invCategoryChevron');
        if (!menu) return;

        const isHidden = menu.classList.contains('hidden');
        menu.classList.toggle('hidden', !isHidden);
        if (chevron) chevron.classList.toggle('rotate-180', isHidden);

        if (isHidden) {
            const input = document.getElementById('invCategorySearchInput');
            if (input) {
                input.value = '';
                window.filterInventoryCategoryOptions('');
                setTimeout(() => input.focus(), 60);
            }
        }
    };

    window.selectInventoryCategory = function (catId, catName, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        const input = document.getElementById('invCategoryIdInput');
        const label = document.getElementById('invCategorySelectedName');
        const menu = document.getElementById('invCategoryDropdownMenu');
        const chevron = document.getElementById('invCategoryChevron');

        if (label) label.textContent = catName;
        if (menu) menu.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-180');

        if (input) {
            input.value = catId;
            const form = input.closest('form');
            if (form) {
                form.requestSubmit();
            }
        }
    };

    window.filterInventoryCategoryOptions = function (query) {
        query = (query || '').trim().toLowerCase();
        const options = document.querySelectorAll('.inv-cat-opt');
        let visibleCount = 0;
        options.forEach(btn => {
            const name = (btn.dataset.name || '').toLowerCase();
            const matches = query === '' || name.includes(query);
            btn.classList.toggle('hidden', !matches);
            if (matches) visibleCount++;
        });
        const emptyMsg = document.getElementById('invCatEmptyMsg');
        if (emptyMsg) {
            emptyMsg.classList.toggle('hidden', visibleCount > 0);
        }
    };

    document.addEventListener('click', function (event) {
        const wrap = document.getElementById('invCategoryDropdownWrap');
        if (wrap && !wrap.contains(event.target)) {
            const menu = document.getElementById('invCategoryDropdownMenu');
            const chevron = document.getElementById('invCategoryChevron');
            if (menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
            }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            const menu = document.getElementById('invCategoryDropdownMenu');
            const chevron = document.getElementById('invCategoryChevron');
            if (menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
            }
        }
    });

    // Observe legacy category select if updated by CategoryManager modal
    const legacySelect = document.querySelector('select[data-category-select="filter"]');
    if (legacySelect && window.MutationObserver) {
        const observer = new MutationObserver(() => {
            const list = document.getElementById('invCategoryOptionsList');
            if (!list) return;
            const currentSelectedId = document.getElementById('invCategoryIdInput')?.value || '';
            let html = `
                <button type="button" onclick="selectInventoryCategory('', 'All Categories', event)"
                    class="inv-cat-opt w-full text-left px-3 py-2 rounded-lg text-xs font-semibold flex items-center justify-between cursor-pointer ${!currentSelectedId ? 'is-selected' : ''}"
                    data-id="" data-name="all categories">
                    <span>All Categories</span>
                    ${!currentSelectedId ? '<i class="fas fa-check text-red-500 text-[10px]"></i>' : ''}
                </button>
            `;
            Array.from(legacySelect.options).forEach(opt => {
                if (!opt.value) return;
                const isSel = String(opt.value) === String(currentSelectedId);
                const optName = opt.textContent;
                html += `
                    <button type="button" data-category-label="${optName}" onclick="selectInventoryCategory('${opt.value}', this.dataset.categoryLabel, event)"
                        class="inv-cat-opt w-full text-left px-3 py-2 rounded-lg text-xs font-semibold flex items-center justify-between cursor-pointer ${isSel ? 'is-selected' : ''}"
                        data-id="${opt.value}" data-name="${optName.toLowerCase()}">
                        <span class="truncate">${optName}</span>
                        ${isSel ? '<i class="fas fa-check text-red-500 text-[10px]"></i>' : ''}
                    </button>
                `;
            });
            html += '<p id="invCatEmptyMsg" class="hidden py-4 text-center text-xs">No categories match your search.</p>';
            list.innerHTML = html;
        });
        observer.observe(legacySelect, { childList: true });
    }
}
</script>
