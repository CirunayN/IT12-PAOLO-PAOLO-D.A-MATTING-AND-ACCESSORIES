@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fas fa-plus-circle text-red-500"></i>
                Add Product / Inventory
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Choose an existing product to add a new stock row, or type a completely new product.
            </p>
        </div>

        <a href="{{ route('products.index') }}"
            class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-300">
            &larr; Back to Catalog
        </a>
    </div>

    @if($errors->any())
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-500 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
        action="{{ route('products.store') }}"
        enctype="multipart/form-data"
        class="glass-card rounded-2xl p-6 sm:p-8 border shadow-lg space-y-6">
        @csrf

        <input type="hidden"
            name="product_mode"
            id="productMode"
            value="{{ old('product_mode', 'new') }}">

        <!-- PRODUCT MODE -->
        <div class="p-1 rounded-2xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 grid grid-cols-2 gap-1">
            <button type="button"
                id="newProductModeBtn"
                onclick="setProductMode('new')"
                class="px-4 py-3 rounded-xl text-sm font-bold transition-all">
                <i class="fas fa-pen mr-1.5"></i>
                Type New Product
            </button>

            <button type="button"
                id="existingProductModeBtn"
                onclick="setProductMode('existing')"
                class="px-4 py-3 rounded-xl text-sm font-bold transition-all">
                <i class="fas fa-list-check mr-1.5"></i>
                Choose Existing Product
            </button>
        </div>

        <!-- EXISTING PRODUCT PICKER -->
        <div id="existingProductSection" class="hidden space-y-4">

            <input type="hidden"
                name="Existing_Product_ID"
                id="existingProductId"
                value="{{ old('Existing_Product_ID') }}">

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Choose Existing Product <span class="text-rose-500">*</span>
                </label>

                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text"
                        id="existingProductSearch"
                        placeholder="Search product name, description or category..."
                        class="w-full pl-11 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                        onfocus="openExistingProductList()"
                        oninput="filterExistingProducts()">
                </div>

                <div id="existingProductList"
                    class="hidden mt-2 max-h-80 overflow-y-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-900 shadow-xl divide-y divide-slate-100 dark:divide-slate-800">

                    @foreach($existingProducts as $prod)
                    @php
                        $stock = (float) $prod->stock_quantity;
                        $cost = (float) $prod->cost_price;
                        $retail = (float) $prod->retail_price;
                        $categoryName = $prod->category->Name ?? 'General';
                        $img = $prod->image_url;
                    @endphp

                    <button type="button"
                        class="existing-product-row w-full p-3 text-left hover:bg-slate-50 dark:hover:bg-dark-800 transition-colors flex items-center justify-between gap-3"
                        data-id="{{ $prod->ID }}"
                        data-name="{{ $prod->Name }}"
                        data-description="{{ $prod->Description ?? '' }}"
                        data-category="{{ $categoryName }}"
                        data-stock="{{ $stock }}"
                        data-cost="{{ $cost }}"
                        data-retail="{{ $retail }}"
                        data-img="{{ $img ?? '' }}"
                        onclick="selectExistingProduct(this)">

                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-lg overflow-hidden bg-slate-100 dark:bg-dark-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center flex-shrink-0">
                                @if($img)
                                <img src="{{ $img }}"
                                    alt="{{ $prod->Name }}"
                                    class="w-full h-full object-cover">
                                @else
                                <i class="fas fa-box text-slate-400"></i>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-500/10 text-red-500">
                                        {{ $categoryName }}
                                    </span>
                                    <span class="text-[10px] text-slate-400">
                                        {{ number_format($stock, 0) }} stock
                                    </span>
                                </div>

                                <div class="text-sm font-bold text-slate-900 dark:text-white truncate mt-0.5">
                                    {{ $prod->Name }}
                                </div>

                                @if($prod->Description)
                                <div class="text-[11px] text-slate-400 truncate">
                                    {{ $prod->Description }}
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="text-right text-xs flex-shrink-0">
                            <div class="font-bold text-emerald-500">
                                ₱{{ number_format($retail, 2) }}
                            </div>
                            <div class="text-slate-400">
                                Cost ₱{{ number_format($cost, 2) }}
                            </div>
                        </div>
                    </button>
                    @endforeach

                    <div id="existingNoMatch"
                        class="hidden p-5 text-center text-xs text-slate-400">
                        No existing product matches your search.
                    </div>
                </div>
            </div>

            <div id="selectedExistingProduct"
                class="hidden p-4 rounded-2xl border-2 border-red-500/30 bg-slate-50 dark:bg-dark-900/80">

                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-200 dark:bg-dark-800 border border-slate-300 dark:border-slate-700 flex items-center justify-center flex-shrink-0">
                        <img id="existingPreviewImage"
                            src=""
                            class="hidden w-full h-full object-cover">

                        <i id="existingPreviewPlaceholder"
                            class="fas fa-box text-slate-400 text-xl"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div id="existingPreviewCategory"
                            class="text-[10px] font-bold text-red-500"></div>

                        <h3 id="existingPreviewName"
                            class="font-black text-slate-900 dark:text-white truncate"></h3>

                        <p id="existingPreviewDescription"
                            class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2"></p>

                        <div class="text-xs text-slate-400 mt-1">
                            Current stock:
                            <strong id="existingPreviewStock"
                                class="text-slate-700 dark:text-slate-200">0</strong>
                        </div>
                    </div>

                    <button type="button"
                        onclick="changeExistingProduct()"
                        class="px-3 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-xs font-bold">
                        Change
                    </button>
                </div>
            </div>
        </div>

        <!-- NEW PRODUCT FIELDS -->
        <div id="newProductSection" class="space-y-5">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Product Name <span class="text-rose-500">*</span>
                </label>

                <input type="text"
                    name="Name"
                    id="newProductName"
                    value="{{ old('Name') }}"
                    placeholder="Type a new product name..."
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">

                <p class="text-[11px] text-slate-400 mt-1">
                    If the product already exists, use “Choose Existing Product” instead of creating a duplicate.
                </p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Description / Other Product Details
                </label>

                <textarea name="Description"
                    id="newProductDescription"
                    rows="4"
                    maxlength="2000"
                    placeholder="Brand, vehicle compatibility, size, material, color, included parts, notes, etc."
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">{{ old('Description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Category <span class="text-rose-500">*</span>
                        </label>

                        <button type="button"
                            onclick="openCategoryCreator()"
                            class="text-xs font-bold text-red-500 hover:text-red-400 flex items-center gap-1">
                            <i class="fas fa-plus-circle"></i>
                            New Category
                        </button>
                    </div>

                    <select name="Category_ID"
                        id="categorySelect" data-category-select="product"
                        class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white">
                        <option value="">Select Category</option>

                        @foreach($categories as $cat)
                        <option value="{{ $cat->ID }}"
                            {{ old('Category_ID') == $cat->ID ? 'selected' : '' }}>
                            {{ $cat->Name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Status <span class="text-rose-500">*</span>
                    </label>

                    <select name="Status_ID"
                        id="statusSelect"
                        class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white">

                        @foreach($statuses as $st)
                        <option value="{{ $st->ID }}"
                            {{ old('Status_ID') == $st->ID ? 'selected' : '' }}>
                            {{ $st->Name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-50 dark:bg-dark-900/70 border border-slate-200 dark:border-slate-700/80 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            <i class="fas fa-images text-red-500 mr-1.5"></i>
                            Product Photos (Up to 5)
                        </label>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Photos are used only when creating a new product.
                        </p>
                    </div>

                    <div class="text-xs font-bold px-3 py-1 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                        <span id="stagedCountDisplay">0</span> / 5
                    </div>
                </div>

                <div id="createDropzone"
                    onclick="document.getElementById('imgUploadInput').click()"
                    class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-red-500 bg-white dark:bg-dark-850 rounded-2xl p-6 text-center cursor-pointer transition-all">

                    <div class="w-12 h-12 mx-auto rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center text-xl">
                        <i class="fas fa-cloud-arrow-up"></i>
                    </div>

                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-2">
                        Click or drag &amp; drop product photos
                    </div>

                    <input type="file"
                        name="images[]"
                        id="imgUploadInput"
                        multiple
                        accept="image/*"
                        class="hidden">
                </div>

                <div id="stagedGallerySection" class="hidden">
                    <div id="imagePreviewsContainer"
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5"></div>
                </div>
            </div>
        </div>

        <!-- INVENTORY BATCH DETAILS -->
        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-700/60 space-y-4">
            <div class="flex items-center gap-2">
                <i class="fas fa-boxes-packing text-red-500"></i>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white">
                        Inventory Batch Details
                    </h4>
                    <p class="text-[11px] text-slate-400">
                        Existing products always create a new stock row here. New products may start with quantity 0.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">
                        Quantity(per piece)<span class="text-rose-500">*</span>
                    </label>

                    <input type="number"
                        name="initial_quantity"
                        id="initialQuantity"
                        value="{{ old('initial_quantity', 0) }}"
                        min="0"
                        step="1"
                        class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">
                        Cost Price (₱)<span class="text-rose-500">*</span>
                    </label>

                    <input type="number"
                        name="cost_price"
                        id="costPrice"
                        value="{{ old('cost_price', 0) }}"
                        min="0"
                        step="0.01"
                        class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">
                        Retail Price (₱)<span class="text-rose-500">*</span>
                    </label>

                    <input type="number"
                        name="retail_price"
                        id="retailPrice"
                        value="{{ old('retail_price', 0) }}"
                        min="0"
                        step="0.01"
                        class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-emerald-600 dark:text-emerald-400">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Condition<span class="text-rose-500">*</span>
                    </label>

                    <select name="condition"
                        id="conditionSelect"
                        class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white">

                        <option value="Good"
                            {{ old('condition', 'Good') === 'Good' ? 'selected' : '' }}>
                            Good
                        </option>

                        <option value="Damaged"
                            {{ old('condition') === 'Damaged' ? 'selected' : '' }}>
                            Damaged
                        </option>

                        <option value="Defective"
                            {{ old('condition') === 'Defective' ? 'selected' : '' }}>
                            Defective
                        </option>
                    </select>

                    <p class="text-[11px] text-slate-400 mt-1">
                        Damaged and defective stock remains recorded but is not sellable in POS.
                    </p>
                </div>

                <div>
                    <label class="flex items-center gap-2 cursor-pointer mt-1">
                        <input type="checkbox"
                            name="has_expiration"
                            id="hasExpiration"
                            value="1"
                            {{ old('has_expiration') ? 'checked' : '' }}
                            onchange="toggleExpiration()"
                            class="rounded border-slate-300 text-red-600 focus:ring-red-500">

                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">
                            This batch has expiration
                        </span>
                    </label>

                    <div id="expirationWrapper" class="mt-3">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            Expiration Date
                        </label>

                        <input type="date"
                            name="expiration_date"
                            id="expirationDate"
                            value="{{ old('expiration_date') }}"
                            min="{{ now()->toDateString() }}"
                            class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm">
                    </div>
                </div>
            </div>

            <div id="existingBatchNotice"
                class="hidden p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-700 dark:text-amber-300">
                <i class="fas fa-code-branch mr-1.5"></i>
                Changing cost/retail price, condition, or expiration does not overwrite the old batch.
                Saving creates a separate inventory row for the selected product.
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('products.index') }}"
                class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 font-bold text-sm">
                Cancel
            </a>

            <button type="submit"
                class="px-7 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25">
                <i class="fas fa-floppy-disk mr-1.5"></i>
                Save
            </button>
        </div>
    </form>
</div>

<!-- NEW CATEGORY MODAL -->
@endsection

@push('scripts')
<script>
const productMode = document.getElementById('productMode');
const newSection = document.getElementById('newProductSection');
const existingSection = document.getElementById('existingProductSection');
const newModeBtn = document.getElementById('newProductModeBtn');
const existingModeBtn = document.getElementById('existingProductModeBtn');
const existingProductId = document.getElementById('existingProductId');
const existingSearch = document.getElementById('existingProductSearch');
const existingList = document.getElementById('existingProductList');
const selectedExistingProduct = document.getElementById('selectedExistingProduct');
const existingBatchNotice = document.getElementById('existingBatchNotice');

const quantityInput = document.getElementById('initialQuantity');
const costInput = document.getElementById('costPrice');
const retailInput = document.getElementById('retailPrice');

function setProductMode(mode) {
    productMode.value = mode;

    const activeClasses = ['bg-red-600', 'text-white', 'shadow-sm'];
    const inactiveClasses = ['text-slate-600', 'dark:text-slate-300'];

    [newModeBtn, existingModeBtn].forEach(btn => {
        btn.classList.remove(...activeClasses);
        btn.classList.add(...inactiveClasses);
    });

    const activeBtn = mode === 'existing'
        ? existingModeBtn
        : newModeBtn;

    activeBtn.classList.remove(...inactiveClasses);
    activeBtn.classList.add(...activeClasses);

    if (mode === 'existing') {
        newSection.classList.add('hidden');
        existingSection.classList.remove('hidden');
        existingBatchNotice.classList.remove('hidden');

        if (!quantityInput.value || Number(quantityInput.value) < 1) {
            quantityInput.value = 1;
        }

        setTimeout(() => existingSearch.focus(), 50);
    } else {
        existingSection.classList.add('hidden');
        newSection.classList.remove('hidden');
        existingBatchNotice.classList.add('hidden');
        existingProductId.value = '';
    }
}

function openExistingProductList() {
    existingList.classList.remove('hidden');
}

function filterExistingProducts() {
    const q = existingSearch.value.toLowerCase().trim();
    let matches = 0;

    document.querySelectorAll('.existing-product-row').forEach(row => {
        const name = (row.dataset.name || '').toLowerCase();
        const description = (row.dataset.description || '').toLowerCase();
        const category = (row.dataset.category || '').toLowerCase();

        const visible =
            name.includes(q) ||
            description.includes(q) ||
            category.includes(q);

        row.classList.toggle('hidden', !visible);

        if (visible) {
            matches++;
        }
    });

    document.getElementById('existingNoMatch')
        .classList.toggle('hidden', matches !== 0);

    openExistingProductList();
}

function selectExistingProduct(row) {
    existingProductId.value = row.dataset.id;
    existingSearch.value = row.dataset.name;
    existingList.classList.add('hidden');

    selectedExistingProduct.classList.remove('hidden');

    document.getElementById('existingPreviewName').textContent =
        row.dataset.name;

    document.getElementById('existingPreviewCategory').textContent =
        row.dataset.category || 'General';

    document.getElementById('existingPreviewDescription').textContent =
        row.dataset.description || 'No description provided.';

    document.getElementById('existingPreviewStock').textContent =
        row.dataset.stock || '0';

    const img = row.dataset.img;
    const imgEl = document.getElementById('existingPreviewImage');
    const placeholder = document.getElementById('existingPreviewPlaceholder');

    if (img) {
        imgEl.src = img;
        imgEl.classList.remove('hidden');
        placeholder.classList.add('hidden');
    } else {
        imgEl.classList.add('hidden');
        placeholder.classList.remove('hidden');
    }

    costInput.value = row.dataset.cost || 0;
    retailInput.value = row.dataset.retail || 0;

    if (!quantityInput.value || Number(quantityInput.value) < 1) {
        quantityInput.value = 1;
    }
}

function changeExistingProduct() {
    selectedExistingProduct.classList.add('hidden');
    existingProductId.value = '';
    existingSearch.value = '';
    filterExistingProducts();
    existingSearch.focus();
}

function toggleExpiration() {
    const checkbox = document.getElementById('hasExpiration');
    const date = document.getElementById('expirationDate');
    const wrapper = document.getElementById('expirationWrapper');

    date.disabled = !checkbox.checked;
    date.required = checkbox.checked;

    wrapper.classList.toggle('opacity-50', !checkbox.checked);

    if (!checkbox.checked) {
        date.value = '';
    }
}

/* IMAGE UPLOAD FOR NEW PRODUCTS */
const stagedDataTransfer = new DataTransfer();
const maxAllowedImages = 5;

const fileInput = document.getElementById('imgUploadInput');
const dropzone = document.getElementById('createDropzone');
const gallerySection = document.getElementById('stagedGallerySection');
const previewsContainer = document.getElementById('imagePreviewsContainer');
const countDisplay = document.getElementById('stagedCountDisplay');

function handleNewFiles(fileList) {
    for (let i = 0; i < fileList.length; i++) {
        const file = fileList[i];

        if (
            file.type.startsWith('image/') &&
            stagedDataTransfer.items.length < maxAllowedImages
        ) {
            stagedDataTransfer.items.add(file);
        }
    }

    fileInput.files = stagedDataTransfer.files;
    renderStagedGallery();
}

function removeStagedImage(index) {
    const newDT = new DataTransfer();

    for (let i = 0; i < stagedDataTransfer.files.length; i++) {
        if (i !== index) {
            newDT.items.add(stagedDataTransfer.files[i]);
        }
    }

    stagedDataTransfer.items.clear();

    for (let i = 0; i < newDT.files.length; i++) {
        stagedDataTransfer.items.add(newDT.files[i]);
    }

    fileInput.files = stagedDataTransfer.files;
    renderStagedGallery();
}

function renderStagedGallery() {
    const selectedFiles = stagedDataTransfer.files;

    countDisplay.textContent = selectedFiles.length;
    previewsContainer.innerHTML = '';

    gallerySection.classList.toggle(
        'hidden',
        selectedFiles.length === 0
    );

    Array.from(selectedFiles).forEach((file, index) => {
        const card = document.createElement('div');

        card.className =
            'relative rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-800 p-2';

        card.innerHTML = `
            <div class="relative aspect-square rounded-lg overflow-hidden">
                <img
                    src="${URL.createObjectURL(file)}"
                    class="w-full h-full object-cover"
                >

                <button
                    type="button"
                    onclick="removeStagedImage(${index})"
                    class="absolute top-1 right-1 w-6 h-6 rounded-full bg-rose-600 text-white"
                >
                    &times;
                </button>
            </div>

            <div class="text-[10px] truncate text-slate-400 mt-1">
                ${file.name}
            </div>
        `;

        previewsContainer.appendChild(card);
    });
}

dropzone.addEventListener('dragover', e => {
    e.preventDefault();
    dropzone.classList.add('border-red-500');
});

dropzone.addEventListener('dragleave', e => {
    e.preventDefault();
    dropzone.classList.remove('border-red-500');
});

dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('border-red-500');

    if (e.dataTransfer && e.dataTransfer.files) {
        handleNewFiles(e.dataTransfer.files);
    }
});

fileInput.addEventListener('change', e => {
    if (e.target.files) {
        handleNewFiles(e.target.files);
    }
});

document.addEventListener('click', e => {
    if (
        !existingSection.contains(e.target) &&
        !existingList.classList.contains('hidden')
    ) {
        existingList.classList.add('hidden');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    toggleExpiration();
    setProductMode(productMode.value);

    if (productMode.value === 'existing' && existingProductId.value) {
        const row = document.querySelector(
            `.existing-product-row[data-id="${existingProductId.value}"]`
        );

        if (row) {
            selectExistingProduct(row);
        }
    }
});
</script>
@endpush
