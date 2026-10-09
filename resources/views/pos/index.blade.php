@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-cash-register text-red-500"></i>
                POS Cashier Terminal
            </h1>
        </div>

        <div class="flex items-center gap-2.5">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-slate-200/90 dark:bg-dark-800 border border-slate-300 dark:border-slate-700 shadow-sm">
                <div class="w-6 h-6 rounded-lg bg-red-500/15 text-red-500 flex items-center justify-center text-xs">
                    <i class="fas fa-user-tag"></i>
                </div>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Cashier:</span>
                <span class="text-xs font-black text-slate-900 dark:text-white">
                    {{ auth()->user()->name }}
                    ({{ auth()->user()->role ?? 'Staff' }})
                </span>
            </div>

            <!-- Mobile-only jump to cart button (< lg) -->
            <button type="button" id="mobileCartJumpBtn" onclick="scrollToMobileCart()"
                class="lg:hidden relative inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs shadow-md shadow-red-600/20 transition-all cursor-pointer">
                <i class="fas fa-basket-shopping"></i>
                <span>Cart</span>
                <span id="mobileCartCount" class="min-w-5 h-5 px-1.5 rounded-full bg-white text-red-600 text-[10px] font-black flex items-center justify-center">0</span>
            </button>
        </div>
    </div>

    <!-- 2-Column POS Workspace: Catalog on Left, Permanently Docked Cart on Right -->
    <div id="posWorkspace">

        <!-- LEFT COLUMN: Product Catalog, Searchable Category & Pagination -->
        <div id="posProducts" class="w-full min-w-0 glass-card rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col shadow-sm">

            <!-- Controls Bar: Category Dropdown + Search Input -->
            <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-slate-800 space-y-3 bg-slate-50/50 dark:bg-dark-850/50">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">

                    <!-- Searchable Category Dropdown -->
                    <div class="relative min-w-[210px] sm:w-64" id="posCategoryDropdownWrap">
                        <button type="button" id="posCategoryDropdownBtn" onclick="togglePosCategoryDropdown(event)"
                            class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-800 dark:text-slate-100 hover:border-red-500/60 shadow-sm transition-all cursor-pointer">
                            <span class="flex items-center gap-2 truncate">
                                <i class="fas fa-layer-group text-red-500"></i>
                                <span id="posCategorySelectedName" class="truncate">All Categories</span>
                            </span>
                            <i id="posCategoryChevron" class="fas fa-chevron-down text-slate-400 text-[10px] transition-transform duration-200"></i>
                        </button>

                        <!-- Searchable Category Dropdown Menu -->
                        <div id="posCategoryDropdownMenu"
                            class="hidden absolute left-0 top-full mt-1.5 w-72 rounded-2xl bg-white dark:bg-dark-850 border border-slate-200 dark:border-slate-700 shadow-2xl z-30 p-2 space-y-2">
                            <!-- Category Search Box -->
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" id="posCategorySearchInput"
                                    placeholder="Search categories..."
                                    oninput="filterCategoryOptions(this.value)"
                                    class="w-full pl-8 pr-3 py-1.5 rounded-lg bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-red-500">
                            </div>

                            <!-- Category Options List -->
                            <div id="posCategoryOptionsList" class="max-h-52 overflow-y-auto space-y-0.5 pr-1">
                                <button type="button" onclick="selectPosCategory('ALL', 'All Categories', event)"
                                    class="pos-cat-opt w-full text-left px-3 py-2 rounded-lg text-xs font-semibold flex items-center justify-between hover:bg-red-50 dark:hover:bg-red-500/10 text-slate-700 dark:text-slate-200 cursor-pointer"
                                    data-id="ALL" data-name="all categories">
                                    <span>All Categories</span>
                                    <span class="text-[10px] text-slate-400">All ({{ count($products) }})</span>
                                </button>
                                @foreach($categories as $c)
                                <button type="button" data-category-label="{{ $c->Name }}" onclick="selectPosCategory('{{ $c->ID }}', this.dataset.categoryLabel, event)"
                                    class="pos-cat-opt w-full text-left px-3 py-2 rounded-lg text-xs font-semibold flex items-center justify-between hover:bg-red-50 dark:hover:bg-red-500/10 text-slate-700 dark:text-slate-200 cursor-pointer"
                                    data-id="{{ $c->ID }}" data-name="{{ strtolower($c->Name) }}">
                                    <span class="truncate">{{ $c->Name }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Live Product Search Bar (Compact Data Grid text removed) -->
                    <div class="relative flex-1 min-w-0">
                        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" id="posSearch"
                            placeholder="Search product name or description..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <button type="button" id="posClearSearchBtn" onclick="resetPosFilters()"
                        class="hidden px-3.5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-600 dark:text-slate-300 text-xs font-bold transition-colors cursor-pointer flex-shrink-0"
                        title="Clear search and category filter">
                        <i class="fas fa-rotate-left mr-1"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Compact Product Table: Fits screen perfectly with table-fixed layout -->
            <div class="flex-1 min-h-[460px] overflow-hidden" id="productGridWrap">
                <table class="w-full table-fixed border-collapse text-left">
                    <thead class="sticky top-0 z-10 bg-slate-100/95 dark:bg-dark-900/95 backdrop-blur border-b border-slate-200 dark:border-slate-800">
                        <tr class="text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="w-14 px-3 py-2.5 font-black">Image</th>
                            <th class="px-3 py-2.5 font-black">Product</th>
                            <th class="w-20 px-2 py-2.5 font-black text-center">Stock</th>
                            <th class="w-28 px-2 py-2.5 font-black text-right">Price</th>
                            <th class="w-20 px-2 py-2.5 font-black text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="productGrid" class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($products as $p)
                        @php
                            $qty = (float) $p->stock_quantity;
                            $price = (float) $p->retail_price;
                            $allImages = $p->all_image_urls;
                            $imagesCount = count($allImages);

                            $sellableBatches = $p->stockIns
                                ->filter(function ($batch) {
                                    $remaining = (float) ($batch->Remaining_Quantity ?? 0);
                                    $condition = $batch->Condition ?? 'Good';
                                    $expirationOkay = !$batch->Has_Expiration || !$batch->Expiration_Date || $batch->Expiration_Date->gte(today());
                                    return $remaining > 0 && $condition === 'Good' && $expirationOkay;
                                })
                                ->sortBy('ID')
                                ->values();

                            $nextExpiry = $sellableBatches
                                ->filter(fn ($b) => $b->Has_Expiration && $b->Expiration_Date)
                                ->sortBy('Expiration_Date')
                                ->first();

                            $oldestBatch = $sellableBatches->first();
                        @endphp

                        <tr class="product-item group hover:bg-red-500/[0.04] dark:hover:bg-red-500/[0.06] transition-colors {{ $qty <= 0 ? 'opacity-50' : '' }}"
                            data-id="{{ $p->ID }}"
                            data-name="{{ $p->Name }}"
                            data-description="{{ $p->Description ?? '' }}"
                            data-price="{{ $price }}"
                            data-stock="{{ $qty }}"
                            data-category="{{ $p->Category_ID }}"
                            data-category-name="{{ $p->category->Name ?? 'General' }}"
                            data-next-expiration="{{ $nextExpiry?->Expiration_Date?->format('M d, Y') ?? 'No expiration' }}"
                            data-batch-count="{{ $sellableBatches->count() }}"
                            data-oldest-batch="{{ $oldestBatch?->created_at?->format('M d, Y') ?? 'N/A' }}">

                            <td class="w-14 px-3 py-2.5">
                                <div class="relative w-11 h-11 rounded-xl overflow-hidden bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-700/80 flex items-center justify-center flex-shrink-0 shadow-sm">
                                    @if($p->image_url)
                                    <img src="{{ $p->image_url }}" alt="{{ $p->Name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    @else
                                    <i class="fas fa-box text-slate-400 opacity-60 text-sm"></i>
                                    @endif

                                    @if($imagesCount > 0)
                                    <button type="button"
                                        data-gallery-images="{{ json_encode($allImages) }}"
                                        data-gallery-title="{{ $p->Name }}"
                                        onclick="openPosGalleryFromButton(this, event)"
                                        class="absolute inset-0 opacity-0 group-hover:opacity-100 bg-black/60 text-white text-[10px] transition-opacity flex items-center justify-center cursor-pointer"
                                        title="View product photos">
                                        <i class="fas fa-images"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>

                            <td class="px-3 py-2.5 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="font-bold text-[13px] text-slate-900 dark:text-white leading-snug line-clamp-2 break-words" title="{{ $p->Name }}">
                                        {{ $p->Name }}
                                    </div>
                                    <button type="button"
                                        onclick="openProductInfo(this.closest('.product-item'), event)"
                                        class="w-6 h-6 rounded-lg hover:bg-slate-200 dark:hover:bg-dark-700 text-slate-400 hover:text-red-500 transition-colors shrink-0 inline-flex items-center justify-center cursor-pointer mt-0.5"
                                        title="View product details &amp; category">
                                        <i class="fas fa-circle-info text-xs"></i>
                                    </button>
                                </div>
                                @if($p->Description)
                                <div class="text-[11px] text-slate-400 truncate mt-0.5 max-w-[320px]" title="{{ $p->Description }}">
                                    {{ $p->Description }}
                                </div>
                                @endif
                            </td>

                            <td class="w-20 px-2 py-2.5 text-center whitespace-nowrap">
                                @if($qty <= 0)
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-black uppercase text-rose-500 bg-rose-500/10 border border-rose-500/25">
                                    Out
                                </span>
                                @elseif($qty <= 5)
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-black text-amber-500 bg-amber-500/10 border border-amber-500/25">
                                    {{ number_format($qty, 0) }} left
                                </span>
                                @else
                                <span class="font-black text-sm text-emerald-600 dark:text-emerald-400">
                                    {{ number_format($qty, 0) }}
                                </span>
                                @endif
                            </td>

                            <td class="w-28 px-2 py-2.5 text-right font-display font-black text-sm sm:text-base text-slate-900 dark:text-white whitespace-nowrap">
                                ₱{{ number_format($price, 2) }}
                            </td>

                            <td class="w-20 px-2 py-2.5 text-center">
                                <button type="button"
                                    {{ $qty <= 0 ? 'disabled' : '' }}
                                    onclick="addProductRowToCart(this)"
                                    class="inline-flex items-center justify-center gap-1.5 w-full h-8 rounded-xl bg-red-600 hover:bg-red-500 disabled:bg-slate-400 disabled:cursor-not-allowed text-white text-xs font-black shadow-sm transition-all active:scale-95 whitespace-nowrap cursor-pointer"
                                    title="Add to cart">
                                    <i class="fas fa-plus text-[10px]"></i>
                                    <span>Add</span>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div id="noProductsMessage" class="hidden text-center py-14 text-slate-400 text-sm">
                    <i class="fas fa-magnifying-glass text-2xl mb-2 opacity-50"></i>
                    <p>No products match the current search or category.</p>
                </div>
            </div>

            <!-- Pagination Bar: Centered Controls (Prev 1 2 Next) -->
            <div id="posPaginationBar" class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-dark-900/70 flex flex-col sm:flex-row items-center justify-center relative gap-2 text-xs">
                <div id="posPaginationCount" class="sm:absolute sm:left-4 text-slate-500 dark:text-slate-400 font-semibold text-[11px]">
                    Showing 1 to 10 of {{ count($products) }} items
                </div>
                <div class="flex items-center justify-center gap-1.5 mx-auto" id="posPaginationControls">
                    <!-- Injected dynamically by JavaScript -->
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Permanently Docked Customer Cart (Slimmer width so catalog is fully visible) -->
        <div id="posCartSidebar" class="w-full min-w-0">
            <div class="glass-card rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/95 dark:bg-dark-850/95 backdrop-blur-xl shadow-xl overflow-hidden flex flex-col">
                <!-- Cart Header -->
                <div class="flex items-center justify-between gap-3 px-4 py-3 bg-slate-100 dark:bg-dark-900 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="font-display font-black text-sm text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-basket-shopping text-red-500"></i>
                            Customer Cart
                        </div>
                        <span id="dockedCartBadge" class="px-2 py-0.5 rounded-full bg-red-500/10 text-red-500 font-black text-[10px]">0 items</span>
                    </div>

                    <button type="button" onclick="promptClearCart()"
                        class="text-[10px] font-black text-rose-500 hover:text-rose-400 transition-colors flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-trash-can text-[10px]"></i> Clear
                    </button>
                </div>

                <!-- Cart Body -->
                <div class="p-3.5 sm:p-4 space-y-3.5">
                    <!-- Selected Items Scrollable List -->
                    <div id="cartContainer" class="space-y-2 max-h-[250px] overflow-y-auto pr-1">
                        <div class="text-center py-8 text-slate-400 text-xs">
                            <i class="fas fa-cart-arrow-down text-3xl mb-2 opacity-50"></i>
                            <p>No items in cart yet.</p>
                        </div>
                    </div>

                    <!-- Cart Summary & Checkout -->
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400">Total Items</span>
                            <strong id="cartTotalItems" class="text-slate-800 dark:text-slate-200">0</strong>
                        </div>

                        <div class="flex items-center justify-between text-base">
                            <span class="font-bold text-slate-700 dark:text-slate-300">Grand Total</span>
                            <strong id="cartGrandTotal" class="font-display font-black text-xl text-emerald-600 dark:text-emerald-400">₱0.00</strong>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Payment Method
                            </label>
                            <select id="paymentMethodSelect" onchange="handlePaymentMethodChange()"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white cursor-pointer">
                                @foreach($paymentMethods as $method)
                                <option value="{{ $method->ID }}" data-name="{{ strtolower($method->Name) }}">{{ $method->Name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="gcashReferenceWrap" class="hidden">
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                GCash Reference Number <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i class="fas fa-hashtag absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" id="gcashReference" inputmode="numeric" maxlength="30"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    class="w-full pl-8 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs font-black text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                                    placeholder="Enter GCash reference no.">
                            </div>
                        </div>

                        <div>
                            <label id="amountTenderedLabel" class="block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Amount Received (₱)
                            </label>
                            <input type="number" id="amountTendered" min="0" step="0.01" inputmode="decimal"
                                oninput="calculateChange()"
                                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-black text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                                placeholder="0.00">
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                            <span class="text-[10px] font-black text-slate-500 dark:text-slate-400">Change Due</span>
                            <strong id="changeDue" class="text-sm font-black text-emerald-500">₱0.00</strong>
                        </div>

                        <button type="button" id="checkoutBtn" onclick="processCheckout()" disabled
                            class="w-full py-2.5 rounded-xl bg-red-600 hover:bg-red-500 disabled:bg-slate-400 disabled:cursor-not-allowed text-white font-black text-xs shadow-md shadow-red-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <i class="fas fa-check-circle"></i>
                            Charge &amp; Save Sale
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div id="productInfoModal"
    class="fixed inset-0 z-[70] bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card max-w-lg w-full rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-2xl space-y-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="mb-1.5 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-red-500/10 text-red-500 font-bold text-xs border border-red-500/20">
                        <i class="fas fa-layer-group text-[10px]"></i>
                        <span id="productInfoCategory">General</span>
                    </span>
                </div>
                <h3 id="productInfoName" class="text-xl font-black text-slate-900 dark:text-white">Product</h3>
            </div>
            <button type="button" onclick="closeProductInfo()"
                class="text-2xl text-slate-400 hover:text-slate-700 dark:hover:text-white leading-none">&times;</button>
        </div>

        <div>
            <div class="text-xs uppercase font-bold text-slate-400 mb-1.5">Description / Other Details</div>
            <p id="productInfoDescription"
                class="text-sm leading-relaxed text-slate-700 dark:text-slate-300 whitespace-pre-line max-h-44 overflow-y-auto">
                No description provided.
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] uppercase text-slate-400 font-bold">Price</div>
                <div id="productInfoPrice" class="font-black text-emerald-500 mt-1">₱0.00</div>
            </div>
            <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] uppercase text-slate-400 font-bold">Available</div>
                <div id="productInfoStock" class="font-black text-slate-900 dark:text-white mt-1">0 units</div>
            </div>
            <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] uppercase text-slate-400 font-bold">Next Expiry</div>
                <div id="productInfoExpiry" class="font-bold text-xs text-amber-500 mt-1">No expiration</div>
            </div>
            <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] uppercase text-slate-400 font-bold">Sellable Batches</div>
                <div id="productInfoBatchCount" class="font-black text-slate-900 dark:text-white mt-1">0</div>
            </div>
            <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 sm:col-span-2">
                <div class="text-[10px] uppercase text-slate-400 font-bold">Oldest Sellable Batch</div>
                <div id="productInfoOldestBatch" class="font-bold text-slate-700 dark:text-slate-300 mt-1">N/A</div>
            </div>
        </div>
    </div>
</div>

<div id="posGalleryModal"
    class="fixed inset-0 z-[70] bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card max-w-3xl w-full rounded-2xl border border-slate-700 p-5 shadow-2xl">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h3 id="posGalleryTitle" class="font-black text-lg text-slate-900 dark:text-white">Product Photos</h3>
                <div id="posGalleryCounter" class="text-xs text-slate-400 mt-0.5">1 / 1</div>
            </div>
            <button type="button" onclick="closePosGallery()" class="text-2xl text-slate-400 hover:text-white">&times;</button>
        </div>

        <div class="relative rounded-2xl overflow-hidden bg-black/30 min-h-[320px] flex items-center justify-center">
            <img id="posGalleryMainImg" src="" alt="Product" class="max-h-[520px] w-full object-contain">
            <button type="button" id="posPrevGalleryBtn" onclick="prevPosGalleryImage()"
                class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 text-white">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" id="posNextGalleryBtn" onclick="nextPosGalleryImage()"
                class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 text-white">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div id="posGalleryThumbnailsStrip" class="flex items-center gap-2 overflow-x-auto pt-4"></div>
    </div>
</div>

<div id="receiptModal"
    class="fixed inset-0 z-[80] bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700 shadow-2xl">
        <div class="text-center mb-4">
            <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/15 text-emerald-500 flex items-center justify-center text-xl">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="font-black text-xl text-slate-900 dark:text-white mt-2">Sale Completed</h3>
            <p id="receiptSaleIdText" class="text-xs text-slate-400"></p>
        </div>
        <div id="receiptContent"
            class="p-4 rounded-xl bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 font-mono"></div>
        <div class="flex items-center gap-3 pt-4">
            <button type="button" onclick="printReceipt()"
                class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-print"></i>
                Print Thermal Receipt
            </button>
            <button type="button" onclick="closeReceiptModal()"
                class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 font-bold text-sm">
                Done
            </button>
        </div>
    </div>
</div>

<div id="cartConfirmModal"
    class="fixed inset-0 z-[80] bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-sm sm:max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
        <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-500 flex items-center justify-center text-xl flex-shrink-0">
                <i id="cartConfirmIcon" class="fas fa-trash-can"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="cartConfirmTitle" class="font-display font-black text-lg text-slate-900 dark:text-white">Remove Item?</h3>
                <p id="cartConfirmSubtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-1">Are you sure?</p>
            </div>
            <button type="button" onclick="closeCartConfirmModal()" class="text-slate-400 hover:text-white text-xl">&times;</button>
        </div>

        <div id="cartConfirmItemBox"
            class="p-3.5 rounded-xl bg-slate-50 dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
            <div class="flex items-center justify-between gap-2">
                <span id="cartConfirmItemName" class="font-bold text-slate-900 dark:text-white text-sm truncate">-</span>
                <span id="cartConfirmItemTotal" class="font-bold text-rose-500 text-sm">₱0.00</span>
            </div>
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[11px]">
                <span id="cartConfirmItemQty">Qty: 1</span>
                <span id="cartConfirmItemPrice">₱0.00 each</span>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button type="button" onclick="closeCartConfirmModal()"
                class="flex-1 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm">
                Cancel
            </button>
            <button type="button" onclick="executeCartRemoval()"
                class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs sm:text-sm">
                <i class="fas fa-trash-can mr-1"></i>
                <span id="cartConfirmAcceptText">Yes, Remove</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let cart = {};
let activeCategory = 'ALL';
let lastCompletedSaleId = null;
let pendingCartRemoval = null;
let currentPosImages = [];
let currentPosIndex = 0;
let cartIsMinimized = false;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function money(value) {
    return '₱' + Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function getCartGrandTotal() {
    return Object.values(cart).reduce((total, item) => total + (item.price * item.qty), 0);
}

function getCartTotalQty() {
    return Object.values(cart).reduce((total, item) => total + item.qty, 0);
}

function getSelectedPaymentName() {
    const select = document.getElementById('paymentMethodSelect');
    const option = select?.selectedOptions?.[0];
    return String(option?.dataset?.name || option?.textContent || '').trim().toLowerCase();
}

function isGcashSelected() {
    return getSelectedPaymentName() === 'gcash';
}

function scrollToMobileCart() {
    const sidebar = document.getElementById('posCartSidebar');
    if (sidebar) {
        sidebar.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function togglePosCategoryDropdown(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('posCategoryDropdownMenu');
    const chevron = document.getElementById('posCategoryChevron');
    if (!menu) return;

    const isHidden = menu.classList.contains('hidden');
    menu.classList.toggle('hidden', !isHidden);
    if (chevron) chevron.classList.toggle('rotate-180', isHidden);

    if (isHidden) {
        const input = document.getElementById('posCategorySearchInput');
        if (input) {
            input.value = '';
            filterCategoryOptions('');
            setTimeout(() => input.focus(), 60);
        }
    }
}

function selectPosCategory(catId, catName, event) {
    if (event) event.stopPropagation();
    activeCategory = String(catId);

    const label = document.getElementById('posCategorySelectedName');
    if (label) label.textContent = catName;

    const menu = document.getElementById('posCategoryDropdownMenu');
    if (menu) menu.classList.add('hidden');

    const chevron = document.getElementById('posCategoryChevron');
    if (chevron) chevron.classList.remove('rotate-180');

    posCurrentPage = 1;
    applyProductFilters();
}

function filterCategoryOptions(query) {
    query = (query || '').trim().toLowerCase();
    document.querySelectorAll('.pos-cat-opt').forEach(btn => {
        const name = (btn.dataset.name || '').toLowerCase();
        btn.classList.toggle('hidden', query !== '' && !name.includes(query));
    });
}

document.addEventListener('click', function (event) {
    const wrap = document.getElementById('posCategoryDropdownWrap');
    if (wrap && !wrap.contains(event.target)) {
        const menu = document.getElementById('posCategoryDropdownMenu');
        const chevron = document.getElementById('posCategoryChevron');
        if (menu) menu.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-180');
    }
});

function addProductRowToCart(button) {
    const row = button.closest('.product-item');
    if (!row) return;

    addToCart(
        row.dataset.id,
        row.dataset.name || 'Product',
        row.dataset.price,
        row.dataset.stock
    );
}

function openPosGalleryFromButton(button, event) {
    if (event) event.stopPropagation();

    let images = [];
    try {
        images = JSON.parse(button.dataset.galleryImages || '[]');
    } catch (error) {
        images = [];
    }

    openPosGallery(
        images,
        button.dataset.galleryTitle || 'Product Photos',
        event
    );
}
function addToCart(id, name, price, maxStock) {
    maxStock = Number(maxStock);
    if (maxStock <= 0) return;

    if (cart[id]) {
        if (cart[id].qty + 1 > maxStock) {
            alert('Cannot exceed available inventory (' + maxStock + ' units).');
            return;
        }
        cart[id].qty += 1;
    } else {
        cart[id] = {
            id: Number(id),
            name: name,
            price: Number(price),
            maxStock: maxStock,
            qty: 1
        };
    }

    renderCart();
}

function updateCartQty(id, delta) {
    if (!cart[id]) return;

    const newQty = cart[id].qty + delta;
    if (newQty <= 0) {
        promptRemoveCartItem(id);
        return;
    }

    if (newQty > cart[id].maxStock) {
        alert('Cannot exceed available inventory (' + cart[id].maxStock + ' units).');
        return;
    }

    cart[id].qty = newQty;
    renderCart();
}

function setCartQuantity(id, value) {
    if (!cart[id]) return;

    let quantity = parseInt(value, 10);
    if (isNaN(quantity) || quantity < 1) quantity = 1;

    if (quantity > cart[id].maxStock) {
        alert('Cannot exceed available inventory (' + cart[id].maxStock + ' units).');
        quantity = cart[id].maxStock;
    }

    cart[id].qty = quantity;
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartContainer');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const keys = Object.keys(cart);
    const totalQty = getCartTotalQty();
    const grandTotal = getCartGrandTotal();

    const elTotal = document.getElementById('cartTotalItems');
    if (elTotal) elTotal.textContent = totalQty;

    const elGrand = document.getElementById('cartGrandTotal');
    if (elGrand) elGrand.textContent = money(grandTotal);

    const elBadge = document.getElementById('dockedCartBadge');
    if (elBadge) elBadge.textContent = `${totalQty} item${totalQty === 1 ? '' : 's'}`;

    const elMobile = document.getElementById('mobileCartCount');
    if (elMobile) elMobile.textContent = totalQty;

    const elTopCount = document.getElementById('topCartCount');
    if (elTopCount) elTopCount.textContent = totalQty;

    const elTopTotal = document.getElementById('topCartTotal');
    if (elTopTotal) elTopTotal.textContent = money(grandTotal);

    window.getPosCartItemCount = function () {
        return totalQty;
    };

    if (keys.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-slate-400 text-xs">
                <i class="fas fa-cart-arrow-down text-3xl mb-2 opacity-50"></i>
                <p>No items in cart yet.</p>
            </div>
        `;
        checkoutBtn.disabled = true;
        document.getElementById('gcashReference').value = '';
        if (isGcashSelected()) document.getElementById('amountTendered').value = '';
        calculateChange();
        return;
    }

    let html = '';
    keys.forEach(k => {
        const item = cart[k];
        const lineTotal = item.price * item.qty;

        html += `
            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-dark-800/80 border border-slate-200 dark:border-slate-700/60 flex items-center justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-xs text-slate-900 dark:text-white truncate">${escapeHtml(item.name)}</div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400">
                        ${money(item.price)} × ${item.qty} =
                        <strong class="text-emerald-500">${money(lineTotal)}</strong>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" onclick="updateCartQty(${item.id}, -1)"
                        class="w-7 h-7 rounded-lg bg-slate-200 dark:bg-dark-700 text-slate-700 dark:text-slate-200 text-xs font-bold">−</button>
                    <input type="number" min="1" max="${item.maxStock}" step="1" value="${item.qty}"
                        onchange="setCartQuantity(${item.id}, this.value)"
                        class="w-12 h-7 px-1 rounded-lg bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-center font-bold text-xs text-slate-900 dark:text-white">
                    <button type="button" onclick="updateCartQty(${item.id}, 1)"
                        class="w-7 h-7 rounded-lg bg-slate-200 dark:bg-dark-700 text-slate-700 dark:text-slate-200 text-xs font-bold">+</button>
                    <button type="button" onclick="promptRemoveCartItem(${item.id})"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 text-xs">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    checkoutBtn.disabled = false;

    if (isGcashSelected()) {
        document.getElementById('amountTendered').value = grandTotal.toFixed(2);
    }

    calculateChange();
}

function handlePaymentMethodChange() {
    const gcash = isGcashSelected();
    const refWrap = document.getElementById('gcashReferenceWrap');
    const refInput = document.getElementById('gcashReference');
    const tendered = document.getElementById('amountTendered');
    const label = document.getElementById('amountTenderedLabel');

    refWrap.classList.toggle('hidden', !gcash);
    refInput.required = gcash;

    if (gcash) {
        label.textContent = 'Amount Paid (₱)';
        tendered.value = getCartGrandTotal() > 0 ? getCartGrandTotal().toFixed(2) : '';
        tendered.readOnly = true;
        tendered.classList.add('opacity-80', 'cursor-not-allowed');
    } else {
        label.textContent = 'Amount Received (₱)';
        tendered.readOnly = false;
        tendered.classList.remove('opacity-80', 'cursor-not-allowed');
        tendered.value = '';
        refInput.value = '';
    }

    calculateChange();
}

function calculateChange() {
    const grandTotal = getCartGrandTotal();
    const tenderedInput = document.getElementById('amountTendered');
    const changeEl = document.getElementById('changeDue');

    if (isGcashSelected()) {
        if (grandTotal > 0) tenderedInput.value = grandTotal.toFixed(2);
        changeEl.textContent = money(0);
        return;
    }

    const tendered = parseFloat(tenderedInput.value) || 0;
    const change = tendered >= grandTotal ? tendered - grandTotal : 0;
    changeEl.textContent = money(change);
}

function promptRemoveCartItem(id) {
    const item = cart[id];
    if (!item) return;

    pendingCartRemoval = { type: 'item', id: id };
    document.getElementById('cartConfirmIcon').className = 'fas fa-trash-can';
    document.getElementById('cartConfirmTitle').innerText = 'Remove Item from Cart?';
    document.getElementById('cartConfirmSubtitle').innerText = 'Remove this item from the active cart?';
    document.getElementById('cartConfirmItemName').innerText = item.name;
    document.getElementById('cartConfirmItemQty').innerText = 'Quantity: ' + item.qty;
    document.getElementById('cartConfirmItemPrice').innerText = 'Unit Price: ' + money(item.price);
    document.getElementById('cartConfirmItemTotal').innerText = money(item.price * item.qty);
    document.getElementById('cartConfirmAcceptText').innerText = 'Yes, Remove';

    const modal = document.getElementById('cartConfirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function promptClearCart() {
    const keys = Object.keys(cart);
    if (keys.length === 0) return;

    pendingCartRemoval = { type: 'clear' };
    document.getElementById('cartConfirmIcon').className = 'fas fa-triangle-exclamation';
    document.getElementById('cartConfirmTitle').innerText = 'Clear Entire Cart?';
    document.getElementById('cartConfirmSubtitle').innerText = 'Remove all items from the current cart?';
    document.getElementById('cartConfirmItemName').innerText = `${keys.length} product(s)`;
    document.getElementById('cartConfirmItemQty').innerText = 'Units: ' + getCartTotalQty();
    document.getElementById('cartConfirmItemPrice').innerText = 'All items will be discarded';
    document.getElementById('cartConfirmItemTotal').innerText = money(getCartGrandTotal());
    document.getElementById('cartConfirmAcceptText').innerText = 'Yes, Clear All';

    const modal = document.getElementById('cartConfirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCartConfirmModal() {
    pendingCartRemoval = null;
    const modal = document.getElementById('cartConfirmModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function executeCartRemoval() {
    if (!pendingCartRemoval) return;

    if (pendingCartRemoval.type === 'item') {
        delete cart[pendingCartRemoval.id];
    } else if (pendingCartRemoval.type === 'clear') {
        cart = {};
    }

    closeCartConfirmModal();
    renderCart();
}

function processCheckout() {
    const keys = Object.keys(cart);
    if (keys.length === 0) {
        alert('Your cart is empty.');
        return;
    }

    const grandTotal = Math.round(getCartGrandTotal() * 100) / 100;
    const paymentMethodId = document.getElementById('paymentMethodSelect').value;
    const gcash = isGcashSelected();
    const gcashReference = document.getElementById('gcashReference').value.trim();
    let tendered = parseFloat(document.getElementById('amountTendered').value);

    if (gcash) {
        if (!gcashReference) {
            alert('Please enter the GCash reference number.');
            document.getElementById('gcashReference').focus();
            return;
        }
        tendered = grandTotal;
    } else {
        if (isNaN(tendered)) {
            alert('Please enter the amount received.');
            return;
        }
        if (tendered < grandTotal) {
            alert(`Insufficient payment.\n\nTotal: ${money(grandTotal)}\nEntered: ${money(tendered)}`);
            return;
        }
    }

    const itemsPayload = keys.map(k => ({
        product_id: cart[k].id,
        quantity: cart[k].qty
    }));

    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    fetch("{{ route('pos.checkout') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            payment_method_id: paymentMethodId,
            amount_tendered: tendered,
            gcash_reference: gcash ? gcashReference : null,
            items: itemsPayload
        })
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) {
            if (data.errors) {
                throw new Error(Object.values(data.errors).flat().join('\n'));
            }
            throw new Error(data.message || 'Unable to complete sale.');
        }
        return data;
    })
    .then(data => {
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML = '<i class="fas fa-check-circle"></i> Charge &amp; Save Sale';

        if (!data.success) {
            alert(data.message || 'Error completing sale.');
            return;
        }

        lastCompletedSaleId = data.sale_id;
        document.getElementById('receiptSaleIdText').textContent = `Sale #${data.sale_id} recorded successfully`;

        let receiptHtml = `
            <div class="text-center font-bold text-sm border-b pb-2 mb-2">PAOLO PAOLO MATTING & ACCESSORIES</div>
            <div>Date: ${escapeHtml(data.date)}</div>
            <div>Cashier: <strong>${escapeHtml(data.cashier)}</strong> (${escapeHtml(data.cashier_role || 'Staff')})</div>
            <div>Payment Method: ${escapeHtml(data.payment_method)}</div>
        `;

        if (data.gcash_reference) {
            receiptHtml += `<div>GCash Ref: <strong>${escapeHtml(data.gcash_reference)}</strong></div>`;
        }

        receiptHtml += '<div class="border-t my-2"></div>';

        data.items.forEach(item => {
            receiptHtml += `
                <div class="flex justify-between gap-2">
                    <span>${escapeHtml(item.name)} (x${item.quantity})</span>
                    <span>${money(item.total)}</span>
                </div>
            `;
        });

        receiptHtml += `
            <div class="border-t my-2"></div>
            <div class="flex justify-between font-bold text-sm"><span>TOTAL:</span><span>${money(data.total)}</span></div>
        `;

        if (String(data.payment_method || '').toLowerCase() === 'cash') {
            receiptHtml += `
                <div class="flex justify-between"><span>Amount Tendered:</span><span>${money(data.tendered)}</span></div>
                <div class="flex justify-between font-bold text-emerald-500"><span>Change:</span><span>${money(data.change)}</span></div>
            `;
        }

        document.getElementById('receiptContent').innerHTML = receiptHtml;
        const receiptModal = document.getElementById('receiptModal');
        receiptModal.classList.remove('hidden');
        receiptModal.classList.add('flex');

        cart = {};
        renderCart();
        document.getElementById('amountTendered').value = '';
        document.getElementById('gcashReference').value = '';
        calculateChange();
    })
    .catch(error => {
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML = '<i class="fas fa-check-circle"></i> Charge &amp; Save Sale';
        alert(error.message || 'Network or server error processing sale.');
    });
}

function printReceipt() {
    if (lastCompletedSaleId) {
        window.open("{{ url('pos/receipt') }}/" + lastCompletedSaleId, '_blank');
    }
}

function closeReceiptModal() {
    const modal = document.getElementById('receiptModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    window.location.reload();
}

let posCurrentPage = 1;
const posPerPage = 10;
let posMatchingRows = [];

function applyProductFilters() {
    const q = (document.getElementById('posSearch')?.value || '').trim().toLowerCase();
    const rows = Array.from(document.querySelectorAll('.product-item'));
    posMatchingRows = [];

    rows.forEach(row => {
        const name = (row.dataset.name || '').toLowerCase();
        const description = (row.dataset.description || '').toLowerCase();
        const categoryName = (row.dataset.categoryName || '').toLowerCase();
        const categoryMatch = activeCategory === 'ALL' || row.dataset.category === activeCategory;
        const searchMatch = !q || name.includes(q) || description.includes(q) || categoryName.includes(q);

        if (categoryMatch && searchMatch) {
            posMatchingRows.push(row);
        }
        row.classList.add('hidden');
    });

    const total = posMatchingRows.length;
    const totalPages = Math.max(1, Math.ceil(total / posPerPage));
    if (posCurrentPage > totalPages) posCurrentPage = 1;

    // Display only the 10 products for the current page
    const startIdx = (posCurrentPage - 1) * posPerPage;
    const endIdx = startIdx + posPerPage;
    posMatchingRows.slice(startIdx, endIdx).forEach(row => {
        row.classList.remove('hidden');
    });

    const noProdMsg = document.getElementById('noProductsMessage');
    if (noProdMsg) noProdMsg.classList.toggle('hidden', total > 0);

    const clearBtn = document.getElementById('posClearSearchBtn');
    if (clearBtn) clearBtn.classList.toggle('hidden', q === '' && activeCategory === 'ALL');

    renderPosPagination(total, totalPages);
}

function renderPosPagination(total, totalPages) {
    const countEl = document.getElementById('posPaginationCount');
    const controlsEl = document.getElementById('posPaginationControls');
    if (!countEl || !controlsEl) return;

    if (total === 0) {
        countEl.textContent = '0 items found';
        controlsEl.innerHTML = '';
        return;
    }

    const start = (posCurrentPage - 1) * posPerPage + 1;
    const end = Math.min(posCurrentPage * posPerPage, total);
    countEl.textContent = `Showing ${start} to ${end} of ${total} items`;

    let html = '';

    // Prev Button
    html += `
        <button type="button" onclick="goToPosPage(${posCurrentPage - 1})"
            ${posCurrentPage === 1 ? 'disabled' : ''}
            class="px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${posCurrentPage === 1 ? 'opacity-40 cursor-not-allowed border-slate-200 dark:border-slate-800 text-slate-400' : 'bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-700 cursor-pointer'}">
            <i class="fas fa-chevron-left text-[10px]"></i> Prev
        </button>
    `;

    // Numbered Buttons (with ellipsis for > 7 pages)
    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7) {
            if (p !== 1 && p !== totalPages && Math.abs(p - posCurrentPage) > 1) {
                if (p === 2 || p === totalPages - 1) {
                    html += `<span class="px-1 text-slate-400 text-xs select-none">...</span>`;
                }
                continue;
            }
        }
        const isActive = p === posCurrentPage;
        html += `
            <button type="button" onclick="goToPosPage(${p})"
                class="min-w-7 h-7 px-2 rounded-lg text-xs font-bold transition-all cursor-pointer ${isActive ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700'}">
                ${p}
            </button>
        `;
    }

    // Next Button
    html += `
        <button type="button" onclick="goToPosPage(${posCurrentPage + 1})"
            ${posCurrentPage === totalPages ? 'disabled' : ''}
            class="px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${posCurrentPage === totalPages ? 'opacity-40 cursor-not-allowed border-slate-200 dark:border-slate-800 text-slate-400' : 'bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-700 cursor-pointer'}">
            Next <i class="fas fa-chevron-right text-[10px]"></i>
        </button>
    `;

    controlsEl.innerHTML = html;
}

function goToPosPage(page) {
    const totalPages = Math.max(1, Math.ceil(posMatchingRows.length / posPerPage));
    if (page < 1 || page > totalPages) return;
    posCurrentPage = page;
    applyProductFilters();
}

function resetPosFilters() {
    const input = document.getElementById('posSearch');
    if (input) input.value = '';
    activeCategory = 'ALL';
    const label = document.getElementById('posCategorySelectedName');
    if (label) label.textContent = 'All Categories';
    posCurrentPage = 1;
    applyProductFilters();
}

function openProductInfo(row, event) {
    if (event) event.stopPropagation();

    document.getElementById('productInfoName').textContent = row.dataset.name || '-';
    document.getElementById('productInfoCategory').textContent = row.dataset.categoryName || 'General';
    document.getElementById('productInfoDescription').textContent = row.dataset.description || 'No description provided.';
    document.getElementById('productInfoPrice').textContent = money(row.dataset.price || 0);
    document.getElementById('productInfoStock').textContent = Number(row.dataset.stock || 0) + ' units';
    document.getElementById('productInfoExpiry').textContent = row.dataset.nextExpiration || 'No expiration';
    document.getElementById('productInfoBatchCount').textContent = row.dataset.batchCount || '0';
    document.getElementById('productInfoOldestBatch').textContent = row.dataset.oldestBatch || 'N/A';

    const modal = document.getElementById('productInfoModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeProductInfo() {
    const modal = document.getElementById('productInfoModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function openPosGallery(images, title, event) {
    if (event) event.stopPropagation();
    if (!images || images.length === 0) return;

    currentPosImages = images;
    currentPosIndex = 0;
    document.getElementById('posGalleryTitle').innerText = title;
    updatePosGalleryDisplay();

    const modal = document.getElementById('posGalleryModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closePosGallery() {
    const modal = document.getElementById('posGalleryModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function updatePosGalleryDisplay() {
    if (!currentPosImages || currentPosImages.length === 0) return;

    document.getElementById('posGalleryMainImg').src = currentPosImages[currentPosIndex];
    document.getElementById('posGalleryCounter').innerText = `${currentPosIndex + 1} / ${currentPosImages.length}`;

    const hideArrows = currentPosImages.length <= 1;
    document.getElementById('posPrevGalleryBtn').classList.toggle('hidden', hideArrows);
    document.getElementById('posNextGalleryBtn').classList.toggle('hidden', hideArrows);

    const strip = document.getElementById('posGalleryThumbnailsStrip');
    strip.innerHTML = '';

    currentPosImages.forEach((url, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `w-12 h-12 rounded-xl border-2 overflow-hidden flex-shrink-0 ${i === currentPosIndex ? 'border-red-500' : 'border-slate-300 dark:border-slate-700 opacity-60'}`;

        const img = document.createElement('img');
        img.src = url;
        img.className = 'w-full h-full object-cover';
        img.alt = 'Product thumbnail';
        btn.appendChild(img);

        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            currentPosIndex = i;
            updatePosGalleryDisplay();
        });

        strip.appendChild(btn);
    });
}

function prevPosGalleryImage() {
    currentPosIndex = currentPosIndex > 0 ? currentPosIndex - 1 : currentPosImages.length - 1;
    updatePosGalleryDisplay();
}

function nextPosGalleryImage() {
    currentPosIndex = currentPosIndex < currentPosImages.length - 1 ? currentPosIndex + 1 : 0;
    updatePosGalleryDisplay();
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('posSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            posCurrentPage = 1;
            applyProductFilters();
        });
    }
    handlePaymentMethodChange();
    renderCart();
    applyProductFilters();
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closePosGallery();
        closeProductInfo();
        closeCartConfirmModal();
    }
});
</script>
@endpush

@push('styles')
<style>
#posWorkspace { display:grid; grid-template-columns:1fr; gap:16px; align-items:start; }
#posCartSidebar { scroll-margin-top:90px; }
#productGridWrap { overflow-x:auto; }
#productGridWrap table { min-width:520px; }
@media (min-width:1024px) {
    #posWorkspace { grid-template-columns:minmax(0,7fr) minmax(0,3fr); }
    #posCartSidebar { position:sticky; top:90px; }
}
</style>
@endpush
