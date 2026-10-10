@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="w-full flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
        @if ($paginator->total())
            <div class="text-slate-500 dark:text-slate-400 font-semibold text-[11px] text-center md:text-left select-none">
                Showing <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->firstItem() }}</span> to <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->lastItem() }}</span> of <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->total() }}</span> items
            </div>
        @else
            <div></div>
        @endif

        <div class="flex items-center justify-center flex-wrap gap-1.5 md:mx-auto">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 rounded-xl border text-xs font-bold transition-all opacity-40 cursor-not-allowed border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 bg-white dark:bg-dark-850 flex items-center gap-1.5 select-none">
                    <i class="fas fa-chevron-left text-[10px]"></i> Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-1.5 rounded-xl border text-xs font-bold transition-all bg-white hover:bg-slate-100 dark:bg-dark-850 dark:hover:bg-dark-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 cursor-pointer flex items-center gap-1.5 shadow-xs">
                    <i class="fas fa-chevron-left text-[10px]"></i> Prev
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="w-6 text-center text-slate-400 text-xs select-none">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-black transition-all bg-red-600 text-white shadow-sm">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-8 h-8 rounded-xl border border-slate-200 dark:border-slate-700 bg-white hover:bg-slate-100 dark:bg-dark-850 dark:hover:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center justify-center transition-all cursor-pointer shadow-xs">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-1.5 rounded-xl border text-xs font-bold transition-all bg-white hover:bg-slate-100 dark:bg-dark-850 dark:hover:bg-dark-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 cursor-pointer flex items-center gap-1.5 shadow-xs">
                    Next <i class="fas fa-chevron-right text-[10px]"></i>
                </a>
            @else
                <span class="px-3 py-1.5 rounded-xl border text-xs font-bold transition-all opacity-40 cursor-not-allowed border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 bg-white dark:bg-dark-850 flex items-center gap-1.5 select-none">
                    Next <i class="fas fa-chevron-right text-[10px]"></i>
                </span>
            @endif
        </div>

        @if ($paginator->total())
            <div class="hidden md:block w-36" aria-hidden="true"></div>
        @endif
    </nav>
@elseif ($paginator->total() > 0)
    <div class="w-full flex items-center justify-center md:justify-start text-xs text-slate-500 dark:text-slate-400 font-semibold text-[11px] select-none">
        <span>Showing <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->firstItem() }}</span> to <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->lastItem() }}</span> of <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->total() }}</span> items</span>
    </div>
@endif
