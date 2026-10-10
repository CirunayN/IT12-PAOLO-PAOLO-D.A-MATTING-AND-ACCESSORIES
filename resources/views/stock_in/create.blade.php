@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fas fa-truck-ramp-box text-red-500"></i>
                Receive Stock Shipment
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Choose an existing product or create a new one, then record the received batch.
            </p>
        </div>
        <a href="{{ route('stock-in.index') }}"
            class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-300 dark:hover:bg-dark-700 transition-colors">
            &larr; Back to Logs
        </a>
    </div>

    @if ($errors->any())
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-sm space-y-1">
        <div class="font-bold flex items-center gap-2">
            <i class="fas fa-circle-exclamation"></i>
            <span>Please correct the errors below:</span>
        </div>
        <ul class="list-disc pl-5 text-xs space-y-0.5">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
        action="{{ route('stock-in.store') }}"
        enctype="multipart/form-data"
        class="glass-card rounded-2xl p-6 sm:p-8 border shadow-lg space-y-6">
        @csrf

        <input type="hidden" name="product_mode" id="productMode" value="{{ old('product_mode', 'existing') }}">
        <input type="hidden" name="Product_ID" id="selectedProductId" value="{{ old('Product_ID', request('product_id')) }}">

        <div class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Product <span class="text-rose-500">*</span>
                    </label>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Existing products reuse their saved name, category and description.
                    </p>
                </div>

                <div class="inline-flex p-1 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-700">
                    <button type="button" id="existingProductModeBtn" onclick="useExistingProduct()"
                        class="px-3 py-2 rounded-lg text-xs font-bold transition-all">
                        <i class="fas fa-box mr-1"></i>
                        Existing Product
                    </button>
                    <button type="button" id="newProductModeBtn" onclick="showNewProductForm()"
                        class="px-3 py-2 rounded-lg text-xs font-bold transition-all">
                        <i class="fas fa-plus mr-1"></i>
                        New Product
                    </button>
                </div>
            </div>

            <div id="selectedProductCard"
                class="hidden p-4 rounded-2xl bg-slate-50 dark:bg-dark-900/90 border-2 border-red-500/40">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-16 h-16 rounded-xl bg-slate-200 dark:bg-dark-800 border border-slate-300 dark:border-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center">
                            <img id="selectedProductImg" src="" alt="Product" class="w-full h-full object-cover hidden">
                            <div id="selectedProductImgPlaceholder" class="text-slate-400 text-xl">
                                <i class="fas fa-boxes-stacked"></i>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span id="selectedProductCategory"
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-red-500/10 text-red-500 border border-red-500/20"></span>
                                <span id="selectedProductStockBadge"
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-md"></span>
                            </div>

                            <h3 id="selectedProductName"
                                class="text-sm sm:text-base font-bold text-slate-900 dark:text-white truncate"></h3>

                            <p id="selectedProductDescription"
                                class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2"></p>

                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 flex flex-wrap items-center gap-2">
                                <span>
                                    Current Cost:
                                    <strong id="selectedProductCostText"
                                        class="text-slate-700 dark:text-slate-300 font-mono">₱0.00</strong>
                                </span>
                                <span>&bull;</span>
                                <span>
                                    Current Retail:
                                    <strong id="selectedProductRetailText"
                                        class="text-emerald-500 font-mono">₱0.00</strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="showProductDropdown()"
                        class="px-3.5 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center gap-1.5 flex-shrink-0">
                        <i class="fas fa-arrows-rotate"></i>
                        <span>Change</span>
                    </button>
                </div>
            </div>

            <div id="productDropdownContainer" class="relative">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="productSearchInput"
                        placeholder="Search by product name, description or category..."
                        class="w-full pl-11 pr-10 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500"
                        onfocus="openProductDropdown()" oninput="filterProductList()">
                    <button type="button" onclick="toggleProductDropdown()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white p-1">
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200" id="dropdownChevron"></i>
                    </button>
                </div>

                <div id="productDropdownList"
                    class="absolute z-30 left-0 right-0 mt-2 max-h-96 overflow-y-auto rounded-2xl bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-700 shadow-2xl divide-y divide-slate-100 dark:divide-slate-800 hidden">

                    @foreach($products as $prod)
                    @php
                        $stock = (float) $prod->stock_quantity;
                        $cost = (float) $prod->cost_price;
                        $retail = (float) $prod->retail_price;
                        $img = $prod->image_url;
                        $categoryName = $prod->category->Name ?? 'General';
                    @endphp

                    <div class="product-option-row p-3 hover:bg-slate-50 dark:hover:bg-dark-800/80 cursor-pointer transition-colors flex items-center justify-between gap-3"
                        data-id="{{ $prod->ID }}"
                        data-name="{{ $prod->Name }}"
                        data-description="{{ $prod->Description ?? '' }}"
                        data-category="{{ $categoryName }}"
                        data-stock="{{ $stock }}"
                        data-cost="{{ $cost }}"
                        data-retail="{{ $retail }}"
                        data-img="{{ $img ?? '' }}"
                        onclick="selectProductFromList(this)">

                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-dark-800 border border-slate-200 dark:border-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                @if($img)
                                <img src="{{ $img }}" alt="{{ $prod->Name }}" class="w-full h-full object-cover">
                                @else
                                <i class="fas fa-boxes-stacked text-slate-400 text-sm"></i>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300">
                                        {{ $categoryName }}
                                    </span>

                                    @if($stock <= 0)
                                    <span class="text-[10px] font-bold text-rose-500 bg-rose-500/10 px-1.5 py-0.5 rounded">
                                        Out of stock
                                    </span>
                                    @elseif($stock <= 5)
                                    <span class="text-[10px] font-bold text-amber-500 bg-amber-500/10 px-1.5 py-0.5 rounded">
                                        {{ number_format($stock, 0) }} in stock
                                    </span>
                                    @else
                                    <span class="text-[10px] font-semibold text-emerald-500 bg-emerald-500/10 px-1.5 py-0.5 rounded">
                                        {{ number_format($stock, 0) }} in stock
                                    </span>
                                    @endif
                                </div>

                                <div class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                    {{ $prod->Name }}
                                </div>

                                @if($prod->Description)
                                <div class="text-[11px] text-slate-400 truncate mt-0.5">
                                    {{ $prod->Description }}
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0 font-mono text-xs">
                            <div class="font-bold text-slate-900 dark:text-white">
                                ₱{{ number_format($retail, 2) }}
                            </div>
                            <div class="text-[11px] text-slate-400">
                                Cost: ₱{{ number_format($cost, 2) }}
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <div id="noMatchMessage" class="p-5 text-center text-xs text-slate-400 hidden">
                        <i class="fas fa-box-open text-2xl mb-2 opacity-50"></i>
                        <p>No products match your search.</p>
                    </div>

                    <button type="button" onclick="showNewProductForm()"
                        class="w-full p-3.5 text-left text-xs font-bold text-red-500 hover:bg-red-500/10 transition-colors">
                        <i class="fas fa-plus-circle mr-1.5"></i>
                        Product not listed? Create a new product
                    </button>
                </div>
            </div>

            <div id="newProductPanel"
                class="hidden p-5 rounded-2xl bg-slate-50 dark:bg-dark-900/60 border border-red-500/30 space-y-4">

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-black text-sm text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-box-open text-red-500"></i>
                            New Product
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Create the product and its first stock batch in one step.
                        </p>
                    </div>

                    <button type="button" onclick="useExistingProduct()"
                        class="text-xs font-bold text-red-500 hover:text-red-400">
                        Select Existing
                    </button>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Product Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="New_Product_Name" id="newProductName"
                        value="{{ old('New_Product_Name') }}"
                        placeholder="e.g. Car Perfume - Black Ice"
                        class="w-full px-4 py-3 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Description / Other Product Details
                    </label>
                    <textarea name="New_Product_Description" rows="4"
                        placeholder="Brand, compatible vehicle/model, size, material, color, notes, etc."
                        class="w-full px-4 py-3 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">{{ old('New_Product_Description') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Category <span class="text-rose-500">*</span>
                    </label>
                    <button type="button" onclick="openCategoryCreator()" class="mb-2 text-xs font-bold text-red-500">+ New Category</button>
                    <select name="New_Category_ID" data-category-select="product"
                        class="w-full px-4 py-3 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->ID }}"
                            {{ old('New_Category_ID') == $cat->ID ? 'selected' : '' }}>
                            {{ $cat->Name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <label for="newProductImages" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <i class="fas fa-images text-red-500 mr-1.5"></i>
                            Product Photos
                        </label>
                        <span class="text-xs font-bold text-slate-400"><span id="newProductImageCount">0</span> / 5</span>
                    </div>
                    <button id="productImageDropzone" type="button" class="w-full rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 p-6 text-center text-sm font-bold" onclick="document.getElementById('newProductImages').click()">
                        <i class="fas fa-cloud-arrow-up text-red-500 block text-2xl mb-2"></i> Drag and drop product photos here, or choose files
                    </button>
                    <input type="file" name="images[]" id="newProductImages" multiple disabled
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        aria-describedby="newProductImagesHelp newProductImagesError"
                        class="hidden">
                    <p id="newProductImagesHelp" class="text-[11px] text-slate-400">
                        Add up to 5 photos (JPG, PNG, WebP or GIF), 5 MB each. The first photo is the main product image.
                    </p>
                    <p id="newProductImagesError" role="alert" class="hidden text-xs text-rose-500"></p>
                    <div id="newProductImagePreviews" class="hidden grid grid-cols-2 sm:grid-cols-5 gap-3"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 pt-2">
            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Quantity Received <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="Quantity" id="qtyInput"
                    value="{{ old('Quantity', 1) }}" min="1" step="1" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-black text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                    oninput="recalculateSummary()">

                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                    <span class="text-[11px] text-slate-400 font-semibold mr-1">Quick:</span>
                    @foreach([5, 10, 25, 50] as $quickQty)
                    <button type="button" onclick="addQty({{ $quickQty }})"
                        class="px-2 py-0.5 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-red-500 hover:text-white text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                        +{{ $quickQty }}
                    </button>
                    @endforeach
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Unit Cost Price (₱) <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="Cost_Price" id="costPriceInput"
                    value="{{ old('Cost_Price', 0) }}" min="0" step="0.01" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                    oninput="recalculateSummary()">
                <p class="text-[11px] text-slate-400">Wholesale cost per unit</p>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Retail Selling Price (₱) <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="Retail_Price" id="retailPriceInput"
                    value="{{ old('Retail_Price', 0) }}" min="0" step="0.01" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-bold text-emerald-600 dark:text-emerald-400 focus:ring-2 focus:ring-red-500"
                    oninput="recalculateSummary()">
                <p class="text-[11px] text-slate-400">POS selling price</p>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-700/60 space-y-4">
            <div class="flex items-center gap-2">
                <i class="fas fa-calendar-check text-red-500"></i>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white">Batch Details</h4>
                    <p class="text-[11px] text-slate-400">
                        Expiration applies only to this received shipment.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="Has_Expiration" id="hasExpiration"
                            value="1" {{ old('Has_Expiration') ? 'checked' : '' }}
                            onchange="toggleExpirationDate()"
                            class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">
                            This batch has an expiration date
                        </span>
                    </label>

                    <div id="expirationDateWrapper" class="mt-3">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            Expiration Date
                        </label>
                        <input type="date" name="Expiration_Date" id="expirationDate"
                            value="{{ old('Expiration_Date') }}"
                            min="{{ now()->toDateString() }}"
                            class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Batch Condition
                    </label>
                    <select name="Condition"
                        class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                        <option value="Good" {{ old('Condition', 'Good') === 'Good' ? 'selected' : '' }}>Good</option>
                        <option value="Damaged" {{ old('Condition') === 'Damaged' ? 'selected' : '' }}>Damaged</option>
                        <option value="Defective" {{ old('Condition') === 'Defective' ? 'selected' : '' }}>Defective</option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1.5">
                        Damaged and defective batches are recorded but excluded from sellable POS stock.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-100 to-slate-50 dark:from-dark-900/90 dark:to-dark-850 border border-slate-200 dark:border-slate-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-lg">
                    <i class="fas fa-calculator"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Batch Summary</div>
                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200 flex flex-wrap items-center gap-2 mt-0.5">
                        <span>Total Cost:
                            <strong id="totalBatchCostDisplay" class="font-mono text-red-500 font-black">₱0.00</strong>
                        </span>
                        <span>&bull;</span>
                        <span>Expected Revenue:
                            <strong id="totalRevenueDisplay" class="font-mono text-emerald-500 font-black">₱0.00</strong>
                        </span>
                    </div>
                </div>
            </div>

            <div class="text-right flex items-center sm:block gap-2">
                <span class="text-xs text-slate-400 font-bold uppercase tracking-wider sm:block">Profit Margin:</span>
                <span id="marginBadge"
                    class="px-2.5 py-1 rounded-xl text-xs font-black bg-emerald-500/15 text-emerald-500 border border-emerald-500/30">
                    ₱0.00 (0%)
                </span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-700/60 space-y-3">
            <div class="flex items-center justify-between">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    <i class="fas fa-user-shield text-red-500 mr-1.5"></i>
                    Processed / Received By
                </label>
                <span class="text-xs text-slate-400">Audit trail</span>
            </div>

            <div class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-dark-850 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white">
                {{ auth()->user()->name }} ({{ auth()->user()->role ?? 'Staff' }}) &mdash; {{ auth()->user()->username }}
            </div>
            <p class="text-[11px] text-slate-400">Automatically recorded as the signed-in user.</p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('stock-in.index') }}"
                class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300 font-bold text-sm transition-colors">
                Cancel
            </a>

            <button type="submit" id="submitBtn"
                class="px-7 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-sm shadow-lg shadow-red-600/25 transition-all flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span>Record Stock-In Batch</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const selectedIdInput = document.getElementById('selectedProductId');
const productModeInput = document.getElementById('productMode');
const searchInput = document.getElementById('productSearchInput');
const dropdownList = document.getElementById('productDropdownList');
const chevron = document.getElementById('dropdownChevron');
const selectedCard = document.getElementById('selectedProductCard');
const dropdownContainer = document.getElementById('productDropdownContainer');
const newProductPanel = document.getElementById('newProductPanel');
const existingModeBtn = document.getElementById('existingProductModeBtn');
const newModeBtn = document.getElementById('newProductModeBtn');
const productImagesInput = document.getElementById('newProductImages');
const productImagePreviews = document.getElementById('newProductImagePreviews');
const productImagesError = document.getElementById('newProductImagesError');
let productImages = [];
let productImageUrls = [];

function renderProductImagePreviews() {
    productImageUrls.forEach(url => URL.revokeObjectURL(url));
    productImageUrls = [];
    productImagePreviews.replaceChildren();

    const transfer = new DataTransfer();
    productImages.forEach(file => transfer.items.add(file));
    productImagesInput.files = transfer.files;
    document.getElementById('newProductImageCount').textContent = productImages.length;
    productImagePreviews.classList.toggle('hidden', productImages.length === 0);

    productImages.forEach((file, index) => {
        const card = document.createElement('div');
        card.className = 'relative rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-800 p-2';

        const photo = document.createElement('img');
        const url = URL.createObjectURL(file);
        productImageUrls.push(url);
        photo.src = url;
        photo.alt = file.name;
        photo.className = 'w-full aspect-square object-cover rounded-lg';

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'absolute top-1 right-1 w-6 h-6 rounded-full bg-rose-600 text-white font-bold';
        remove.textContent = '\u00d7';
        remove.setAttribute('aria-label', 'Remove ' + file.name);
        remove.addEventListener('click', () => {
            productImages.splice(index, 1);
            productImagesError.classList.add('hidden');
            renderProductImagePreviews();
        });

        const filename = document.createElement('p');
        filename.className = 'text-[10px] truncate text-slate-400 mt-1';
        filename.textContent = file.name;
        card.append(photo, remove, filename);
        productImagePreviews.appendChild(card);
    });
}

function addProductImages(files) {
    const errors = new Set();
    for (const file of files) {
        if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type)) {
            errors.add('Choose JPG, PNG, WebP or GIF images.');
        } else if (file.size > 5 * 1024 * 1024) {
            errors.add('Each photo must be 5 MB or smaller.');
        } else if (productImages.length >= 5) {
            errors.add('You can upload up to 5 photos.');
        } else {
            productImages.push(file);
        }
    }
    productImagesError.textContent = [...errors].join(' ');
    productImagesError.classList.toggle('hidden', errors.size === 0);
    renderProductImagePreviews();
}
productImagesInput.addEventListener('change', () => addProductImages(productImagesInput.files));
const imageDropzone = document.getElementById('productImageDropzone');
imageDropzone.addEventListener('dragover', event => { event.preventDefault(); imageDropzone.classList.add('border-red-500'); });
imageDropzone.addEventListener('dragleave', () => imageDropzone.classList.remove('border-red-500'));
imageDropzone.addEventListener('drop', event => {
    event.preventDefault(); imageDropzone.classList.remove('border-red-500');
    if (!productImagesInput.disabled) addProductImages(event.dataTransfer.files);
});

const qtyInput = document.getElementById('qtyInput');
const costInput = document.getElementById('costPriceInput');
const retailInput = document.getElementById('retailPriceInput');

const totalBatchCostDisplay = document.getElementById('totalBatchCostDisplay');
const totalRevenueDisplay = document.getElementById('totalRevenueDisplay');
const marginBadge = document.getElementById('marginBadge');

function styleModeButtons(mode) {
    productImagesInput.disabled = mode !== 'new';
    const activeClasses = ['bg-red-600', 'text-white', 'shadow-sm'];
    const inactiveClasses = ['text-slate-600', 'dark:text-slate-300'];

    [existingModeBtn, newModeBtn].forEach(btn => {
        btn.classList.remove(...activeClasses);
        btn.classList.add(...inactiveClasses);
    });

    const active = mode === 'new' ? newModeBtn : existingModeBtn;
    active.classList.remove(...inactiveClasses);
    active.classList.add(...activeClasses);
}

function toggleProductDropdown() {
    dropdownList.classList.contains('hidden')
        ? openProductDropdown()
        : closeProductDropdown();
}

function openProductDropdown() {
    dropdownList.classList.remove('hidden');
    chevron.classList.add('rotate-180');
}

function closeProductDropdown() {
    dropdownList.classList.add('hidden');
    chevron.classList.remove('rotate-180');
}

function filterProductList() {
    const q = searchInput.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.product-option-row');
    let matches = 0;

    rows.forEach(row => {
        const name = (row.dataset.name || '').toLowerCase();
        const description = (row.dataset.description || '').toLowerCase();
        const category = (row.dataset.category || '').toLowerCase();

        if (name.includes(q) || description.includes(q) || category.includes(q)) {
            row.classList.remove('hidden');
            matches++;
        } else {
            row.classList.add('hidden');
        }
    });

    document.getElementById('noMatchMessage')
        .classList.toggle('hidden', matches !== 0);

    openProductDropdown();
}

function selectProductFromList(row) {
    productModeInput.value = 'existing';
    styleModeButtons('existing');
    newProductPanel.classList.add('hidden');

    const id = row.dataset.id;
    const name = row.dataset.name;
    const description = row.dataset.description || 'No description provided.';
    const category = row.dataset.category;
    const stock = parseFloat(row.dataset.stock) || 0;
    const cost = parseFloat(row.dataset.cost) || 0;
    const retail = parseFloat(row.dataset.retail) || 0;
    const img = row.dataset.img;

    selectedIdInput.value = id;
    document.getElementById('selectedProductName').textContent = name;
    document.getElementById('selectedProductDescription').textContent = description;
    document.getElementById('selectedProductCategory').textContent = category;

    const stockBadge = document.getElementById('selectedProductStockBadge');

    if (stock <= 0) {
        stockBadge.className =
            'text-[10px] font-bold px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-500 border border-rose-500/20';
        stockBadge.textContent = 'Out of Stock (0)';
    } else if (stock <= 5) {
        stockBadge.className =
            'text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-500 border border-amber-500/20';
        stockBadge.textContent = stock + ' units remaining';
    } else {
        stockBadge.className =
            'text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-500 border border-emerald-500/20';
        stockBadge.textContent = stock + ' in stock';
    }

    document.getElementById('selectedProductCostText').textContent = '₱' + cost.toFixed(2);
    document.getElementById('selectedProductRetailText').textContent = '₱' + retail.toFixed(2);

    const imgEl = document.getElementById('selectedProductImg');
    const imgPlaceholder = document.getElementById('selectedProductImgPlaceholder');

    if (img) {
        imgEl.src = img;
        imgEl.classList.remove('hidden');
        imgPlaceholder.classList.add('hidden');
    } else {
        imgEl.classList.add('hidden');
        imgPlaceholder.classList.remove('hidden');
    }

    costInput.value = cost;
    retailInput.value = retail;

    selectedCard.classList.remove('hidden');
    dropdownContainer.classList.add('hidden');
    closeProductDropdown();
    recalculateSummary();
}

function showProductDropdown() {
    productModeInput.value = 'existing';
    styleModeButtons('existing');
    newProductPanel.classList.add('hidden');
    selectedCard.classList.add('hidden');
    dropdownContainer.classList.remove('hidden');

    searchInput.value = '';
    filterProductList();

    setTimeout(() => searchInput.focus(), 50);
}

function showNewProductForm() {
    productModeInput.value = 'new';
    selectedIdInput.value = '';
    styleModeButtons('new');

    selectedCard.classList.add('hidden');
    dropdownContainer.classList.add('hidden');
    newProductPanel.classList.remove('hidden');
    closeProductDropdown();

    setTimeout(() => document.getElementById('newProductName').focus(), 50);
}

function useExistingProduct() {
    productModeInput.value = 'existing';
    styleModeButtons('existing');
    newProductPanel.classList.add('hidden');

    if (selectedIdInput.value) {
        const row = document.querySelector(
            `.product-option-row[data-id="${selectedIdInput.value}"]`
        );

        if (row) {
            selectProductFromList(row);
            return;
        }
    }

    selectedCard.classList.add('hidden');
    dropdownContainer.classList.remove('hidden');
    openProductDropdown();
    setTimeout(() => searchInput.focus(), 50);
}

function toggleExpirationDate() {
    const checkbox = document.getElementById('hasExpiration');
    const date = document.getElementById('expirationDate');
    const wrapper = document.getElementById('expirationDateWrapper');

    date.disabled = !checkbox.checked;
    date.required = checkbox.checked;
    wrapper.classList.toggle('opacity-50', !checkbox.checked);

    if (!checkbox.checked) {
        date.value = '';
    }
}

function addQty(amount) {
    const current = parseInt(qtyInput.value) || 0;
    qtyInput.value = Math.max(1, current + amount);
    recalculateSummary();
}

function recalculateSummary() {
    const qty = parseFloat(qtyInput.value) || 0;
    const cost = parseFloat(costInput.value) || 0;
    const retail = parseFloat(retailInput.value) || 0;

    const totalCost = qty * cost;
    const totalRevenue = qty * retail;
    const profit = totalRevenue - totalCost;
    const markupPct = cost > 0 ? ((retail - cost) / cost) * 100 : 0;

    totalBatchCostDisplay.textContent =
        '₱' + totalCost.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    totalRevenueDisplay.textContent =
        '₱' + totalRevenue.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    if (profit >= 0) {
        marginBadge.className =
            'px-2.5 py-1 rounded-xl text-xs font-black bg-emerald-500/15 text-emerald-500 border border-emerald-500/30';

        marginBadge.textContent =
            '+₱' + profit.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' (' + markupPct.toFixed(1) + '% markup)';
    } else {
        marginBadge.className =
            'px-2.5 py-1 rounded-xl text-xs font-black bg-rose-500/15 text-rose-500 border border-rose-500/30';

        marginBadge.textContent =
            '-₱' + Math.abs(profit).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' (Loss)';
    }
}

document.addEventListener('click', e => {
    if (!dropdownContainer.contains(e.target)) {
        closeProductDropdown();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    toggleExpirationDate();
    recalculateSummary();

    if (productModeInput.value === 'new') {
        showNewProductForm();
        return;
    }

    styleModeButtons('existing');

    if (selectedIdInput.value) {
        const row = document.querySelector(
            `.product-option-row[data-id="${selectedIdInput.value}"]`
        );

        if (row) {
            selectProductFromList(row);
            const qty = document.getElementById('qtyInput');
            if (qty) {
                setTimeout(() => {
                    qty.focus();
                    qty.select();
                }, 100);
            }
        }
    }
});
</script>
@endpush
