@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fas fa-plus-circle text-red-500"></i>
                Add New Product
            </h1>
        </div>
        <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-300">
            &larr; Back to Catalog
        </a>
    </div>

    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="glass-card rounded-2xl p-6 sm:p-8 border shadow-lg space-y-6">
        @csrf

        <!-- Product Name -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                Product Name <span class="text-rose-500">*</span>
            </label>
            <input type="text" name="Name" value="{{ old('Name') }}" required placeholder="e.g. Deep Dish Matting - Ford Ranger 2024"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Category -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Category <span class="text-rose-500">*</span>
                </label>
                <select name="Category_ID" required class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white">
                    <option value="">Select Category</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->ID }}" {{ old('Category_ID') == $cat->ID ? 'selected' : '' }}>{{ $cat->Name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Status <span class="text-rose-500">*</span>
                </label>
                <select name="Status_ID" required class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white">
                    @foreach($statuses as $st)
                    <option value="{{ $st->ID }}" {{ old('Status_ID') == $st->ID ? 'selected' : '' }}>{{ $st->Name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- MULTI-IMAGE DRAG & DROP UPLOAD SECTION -->
        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-dark-900/70 border border-slate-200 dark:border-slate-700/80 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        <i class="fas fa-images text-red-500 mr-1.5"></i> Product Photos (Up to 5 Images)
                    </label>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Upload photos together or one-by-one. Drag &amp; drop supported.
                    </p>
                </div>
                <div class="text-xs font-bold px-3 py-1 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 self-start sm:self-auto">
                    <span id="stagedCountDisplay">0</span> of 5 photos selected
                </div>
            </div>

            <!-- Dropzone Area -->
            <div id="createDropzone" onclick="document.getElementById('imgUploadInput').click()"
                class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-red-500 dark:hover:border-cyan-400 bg-white dark:bg-dark-850 rounded-2xl p-6 text-center cursor-pointer transition-all group">
                <div class="flex flex-col items-center justify-center space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-red-500/10 group-hover:bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center text-xl transition-colors">
                        <i class="fas fa-cloud-arrow-up"></i>
                    </div>
                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200">
                        Click to browse or drag &amp; drop photos here
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm">
                        Select multiple photos at once or add photos one-by-one (JPG, PNG, WEBP up to 5MB each)
                    </p>
                </div>

                <input type="file" name="images[]" id="imgUploadInput" multiple accept="image/*" class="hidden">
            </div>

            <!-- Staged Images Live Preview Gallery -->
            <div id="stagedGallerySection" class="hidden space-y-2">
                <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    Selected Photos (First photo will be Main Cover):
                </div>
                <div id="imagePreviewsContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5"></div>
            </div>
        </div>

        <!-- Initial Stock-In Section -->
        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-700/60 space-y-4">
            <div class="flex items-center gap-2">
                <i class="fas fa-boxes-packing text-red-500"></i>
                <h4 class="font-bold text-sm text-slate-900 dark:text-white">Initial Stock Delivery</h4>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Quantity (Units)</label>
                    <input type="number" name="initial_quantity" value="{{ old('initial_quantity', 0) }}" min="0" step="1"
                        class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Cost Price (₱)</label>
                    <input type="number" name="cost_price" value="{{ old('cost_price', 0) }}" min="0" step="0.01"
                        class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Retail Selling Price (₱)</label>
                    <input type="number" name="retail_price" value="{{ old('retail_price', 0) }}" min="0" step="0.01"
                        class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-300 dark:border-slate-700 text-sm font-bold">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('products.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 font-bold text-sm">
                Cancel
            </a>
            <button type="submit" class="px-7 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25">
                Save Product
            </button>
        </div>
    </form>
</div>

<script>
// Cumulative DataTransfer for drag & drop multi-image staging
const stagedDataTransfer = new DataTransfer();
const maxAllowedImages = 5;

const fileInput = document.getElementById('imgUploadInput');
const dropzone = document.getElementById('createDropzone');
const gallerySection = document.getElementById('stagedGallerySection');
const previewsContainer = document.getElementById('imagePreviewsContainer');
const countDisplay = document.getElementById('stagedCountDisplay');

function handleNewFiles(fileList) {
    let addedAny = false;
    let exceededMax = false;

    for (let i = 0; i < fileList.length; i++) {
        const file = fileList[i];
        if (!file.type.startsWith('image/')) continue;

        if (stagedDataTransfer.items.length < maxAllowedImages) {
            stagedDataTransfer.items.add(file);
            addedAny = true;
        } else {
            exceededMax = true;
        }
    }

    if (exceededMax) {
        alert('Maximum of 5 photos reached. Only the first 5 photos were kept.');
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
    const files = stagedDataTransfer.files;
    countDisplay.textContent = files.length;
    previewsContainer.innerHTML = '';

    if (files.length === 0) {
        gallerySection.classList.add('hidden');
        return;
    }

    gallerySection.classList.remove('hidden');

    Array.from(files).forEach((file, index) => {
        const card = document.createElement('div');
        card.className = 'relative rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-800 p-2 shadow-sm group';

        const thumbBox = document.createElement('div');
        thumbBox.className = 'relative w-full aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-dark-900';

        const img = document.createElement('img');
        img.className = 'w-full h-full object-cover';
        img.src = URL.createObjectURL(file);
        thumbBox.appendChild(img);

        if (index === 0) {
            const coverBadge = document.createElement('span');
            coverBadge.className = 'absolute top-1.5 left-1.5 px-2 py-0.5 rounded-md bg-red-600 text-white text-[10px] font-bold shadow-sm';
            coverBadge.textContent = 'Cover';
            thumbBox.appendChild(coverBadge);
        }

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs opacity-90 hover:opacity-100 shadow-md cursor-pointer';
        removeBtn.innerHTML = '&times;';
        removeBtn.onclick = (e) => {
            e.stopPropagation();
            removeStagedImage(index);
        };
        thumbBox.appendChild(removeBtn);

        card.appendChild(thumbBox);

        const nameLabel = document.createElement('div');
        nameLabel.className = 'text-[11px] text-slate-500 truncate mt-1.5 px-1';
        nameLabel.textContent = file.name;
        card.appendChild(nameLabel);

        previewsContainer.appendChild(card);
    });
}

// Drag & Drop events
dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    e.stopPropagation();
    dropzone.classList.add('border-red-500', 'bg-cyan-50/20');
});

dropzone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    e.stopPropagation();
    dropzone.classList.remove('border-red-500', 'bg-cyan-50/20');
});

dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    e.stopPropagation();
    dropzone.classList.remove('border-red-500', 'bg-cyan-50/20');
    if (e.dataTransfer && e.dataTransfer.files) {
        handleNewFiles(e.dataTransfer.files);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files) {
        handleNewFiles(e.target.files);
    }
});
</script>
@endsection