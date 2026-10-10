@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="w-full flex flex-col sm:flex-row items-center justify-center relative gap-3 text-xs">
        @if ($paginator->total())
            <div class="sm:absolute sm:left-0 text-slate-500 dark:text-slate-400 font-semibold text-[11px] text-center sm:text-left select-none">
                Showing <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->firstItem() }}</span> to <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->lastItem() }}</span> of <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->total() }}</span> items
            </div>
        @endif

        <div class="flex items-center justify-center flex-wrap gap-1.5 mx-auto">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="px-2.5 py-1 rounded-lg border text-xs font-bold transition-all opacity-40 cursor-not-allowed border-slate-200 dark:border-slate-800 text-slate-400 flex items-center gap-1 select-none">
                    <i class="fas fa-chevron-left text-[10px]"></i> Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-2.5 py-1 rounded-lg border text-xs font-bold transition-all bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-700 cursor-pointer flex items-center gap-1">
                    <i class="fas fa-chevron-left text-[10px]"></i> Prev
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="px-1 text-slate-400 text-xs select-none">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="min-w-[28px] h-7 px-2 flex items-center justify-center rounded-lg text-xs font-bold transition-all bg-red-600 text-white shadow-sm">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="min-w-[28px] h-7 px-2 flex items-center justify-center rounded-lg text-xs font-bold transition-all cursor-pointer bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-2.5 py-1 rounded-lg border text-xs font-bold transition-all bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-700 cursor-pointer flex items-center gap-1">
                    Next <i class="fas fa-chevron-right text-[10px]"></i>
                </a>
            @else
                <span class="px-2.5 py-1 rounded-lg border text-xs font-bold transition-all opacity-40 cursor-not-allowed border-slate-200 dark:border-slate-800 text-slate-400 flex items-center gap-1 select-none">
                    Next <i class="fas fa-chevron-right text-[10px]"></i>
                </span>
            @endif
        </div>
    </nav>
@elseif ($paginator->total() > 0)
    <div class="w-full flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-semibold text-[11px] select-none">
        <span>Showing <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->firstItem() }}</span> to <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->lastItem() }}</span> of <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->total() }}</span> items</span>
    </div>
@endif
