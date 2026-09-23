@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fas fa-edit text-red-500"></i>
                Edit Product #{{ $product->ID }}
            </h1>
        </div>
        <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-300">
            &larr; Back to Catalog
        </a>
    </div>

    <form method="POST" action="{{ route('products.update', $product->ID) }}" enctype="multipart/form-data" class="glass-card rounded-2xl p-6 sm:p-8 border shadow-lg space-y-6">
        @csrf
        @method('PUT')

        <!-- Product Name -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                Product Name <span class="text-rose-500">*</span>
            </label>
            <input type="text" name="Name" value="{{ old('Name', $product->Name) }}" required
                class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Category -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Category <span class="text-rose-500">*</span>
                    </label>
                    <button type="button" onclick="openNewCategoryModal()" class="text-xs font-bold text-red-500 hover:text-red-400 flex items-center gap-1 transition-colors">
                        <i class="fas fa-plus-circle"></i> New Category
                    </button>
                </div>
                <select name="Category_ID" id="categorySelect" required class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                    @foreach($categories as $cat)
                    <option value="{{ $cat->ID }}" {{ old('Category_ID', $product->Category_ID) == $cat->ID ? 'selected' : '' }}>{{ $cat->Name }}</option>
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
                    <option value="{{ $st->ID }}" {{ old('Status_ID', $product->Status_ID) == $st->ID ? 'selected' : '' }}>{{ $st->Name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Current Images & Multi-Image Gallery Manager -->
        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-dark-900/70 border border-slate-200 dark:border-slate-700/80 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        <i class="fas fa-images text-red-500 mr-1.5"></i> Product Photos (Max 5 Photos)
                    </label>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Click "Remove" to delete unwanted photos. Add more using the drag &amp; drop area below.
                    </p>
                </div>
            </div>

            @php
                $existingImages = is_array($product->Images) && count($product->Images) > 0 
                    ? $product->Images 
                    : ($product->Image ? [$product->Image] : []);
            @endphp

            @if(count($existingImages) > 0)
            <div>
                <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                    Current Photos on File:
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5">
                    @foreach($existingImages as $rawImg)
                    <div id="imageCard{{ $loop->index }}" class="group relative rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-800 p-2 shadow-sm transition-all">
                        <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-dark-900">
                            <img src="{{ asset($rawImg) }}" alt="Photo {{ $loop->iteration }}" class="w-full h-full object-cover">

                            @if($loop->first)
                            <span class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded-md bg-cyan-600 text-white text-[10px] font-bold shadow-sm z-10">
                                Cover Photo
                            </span>
                            @endif

                            <div id="removalOverlay{{ $loop->index }}" class="hidden absolute inset-0 bg-rose-950/90 backdrop-blur-[2px] flex flex-col items-center justify-center text-center p-2 z-20">
                                <i class="fas fa-trash-can text-rose-400 text-2xl mb-1 animate-pulse"></i>
                                <span class="text-[11px] font-black text-white">Will be removed</span>
                                <span class="text-[9px] text-rose-300 mt-0.5">Click Undo to keep</span>
                            </div>
                        </div>

                        <div class="mt-2">
                            <button type="button" id="btnRemove{{ $loop->index }}" onclick="markRemoval({{ $loop->index }}, '{{ addslashes($rawImg) }}')"
                                class="w-full py-1 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 text-xs font-bold flex items-center justify-center gap-1 cursor-pointer">
                                <i class="fas fa-trash-alt text-[10px]"></i> Remove
                            </button>
                            <button type="button" id="btnUndo{{ $loop->index }}" onclick="undoRemoval({{ $loop->index }})"
                                class="hidden w-full py-1 rounded-lg bg-cyan-50 hover:bg-cyan-100 dark:bg-cyan-950/40 text-cyan-700 dark:text-cyan-300 border border-cyan-300 dark:border-cyan-700 text-xs font-bold flex items-center justify-center gap-1 cursor-pointer">
                                <i class="fas fa-rotate-left text-[10px]"></i> Undo
                            </button>
                        </div>
                        <div id="hiddenInputContainer{{ $loop->index }}"></div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Drag & drop area for additional photos -->
            <div id="editDropzone" onclick="document.getElementById('editImgUploadInput').click()"
                class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-red-500 dark:hover:border-cyan-400 bg-white dark:bg-dark-850 rounded-2xl p-5 text-center cursor-pointer transition-all group">
                <div class="flex flex-col items-center justify-center space-y-1.5">
                    <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400 flex items-center justify-center text-lg">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                        Add more photos (Drag &amp; drop or click to browse)
                    </div>
                </div>
                <input type="file" name="images[]" id="editImgUploadInput" multiple accept="image/*" class="hidden">
            </div>

            <div id="editStagedSection" class="hidden space-y-2">
                <div class="text-[11px] font-bold text-red-500 uppercase tracking-wider">New Photos Ready to Upload:</div>
                <div id="editPreviewsContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5"></div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('products.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 font-bold text-sm">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25">
                Update Product
            </button>
        </div>
    </form>
</div>

<script>
function markRemoval(index, rawImg) {
    document.getElementById('removalOverlay' + index).classList.remove('hidden');
    document.getElementById('btnRemove' + index).classList.add('hidden');
    document.getElementById('btnUndo' + index).classList.remove('hidden');

    const container = document.getElementById('hiddenInputContainer' + index);
    container.innerHTML = `<input type="hidden" name="remove_images[]" value="${rawImg}">`;
}

function undoRemoval(index) {
    document.getElementById('removalOverlay' + index).classList.add('hidden');
    document.getElementById('btnRemove' + index).classList.remove('hidden');
    document.getElementById('btnUndo' + index).classList.add('hidden');

    const container = document.getElementById('hiddenInputContainer' + index);
    container.innerHTML = '';
}

// Staging for new uploads in edit
const editDataTransfer = new DataTransfer();
const editInput = document.getElementById('editImgUploadInput');
const editDropzone = document.getElementById('editDropzone');
const editStagedSection = document.getElementById('editStagedSection');
const editPreviewsContainer = document.getElementById('editPreviewsContainer');

function handleEditFiles(fileList) {
    for (let i = 0; i < fileList.length; i++) {
        const file = fileList[i];
        if (file.type.startsWith('image/')) {
            editDataTransfer.items.add(file);
        }
    }
    editInput.files = editDataTransfer.files;
    renderEditGallery();
}

function renderEditGallery() {
    const files = editDataTransfer.files;
    editPreviewsContainer.innerHTML = '';

    if (files.length === 0) {
        editStagedSection.classList.add('hidden');
        return;
    }

    editStagedSection.classList.remove('hidden');

    Array.from(files).forEach((file, index) => {
        const card = document.createElement('div');
        card.className = 'relative rounded-2xl border border-red-500/40 bg-white dark:bg-dark-800 p-2 shadow-sm';
        card.innerHTML = `
            <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-dark-900">
                <img src="${URL.createObjectURL(file)}" class="w-full h-full object-cover">
                <button type="button" onclick="removeEditStagedImage(${index})" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs cursor-pointer">&times;</button>
            </div>
            <div class="text-[10px] text-slate-400 truncate mt-1">${file.name}</div>
        `;
        editPreviewsContainer.appendChild(card);
    });
}

function removeEditStagedImage(index) {
    const newDT = new DataTransfer();
    for (let i = 0; i < editDataTransfer.files.length; i++) {
        if (i !== index) newDT.items.add(editDataTransfer.files[i]);
    }
    editDataTransfer.items.clear();
    for (let i = 0; i < newDT.files.length; i++) editDataTransfer.items.add(newDT.files[i]);
    editInput.files = editDataTransfer.files;
    renderEditGallery();
}

editDropzone.addEventListener('dragover', (e) => { e.preventDefault(); editDropzone.classList.add('border-red-500'); });
editDropzone.addEventListener('dragleave', (e) => { e.preventDefault(); editDropzone.classList.remove('border-red-500'); });
editDropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    editDropzone.classList.remove('border-red-500');
    if (e.dataTransfer && e.dataTransfer.files) handleEditFiles(e.dataTransfer.files);
});
editInput.addEventListener('change', (e) => { if (e.target.files) handleEditFiles(e.target.files); });

// Category Modal Functions
function openNewCategoryModal() {
    document.getElementById('newCategoryInput').value = '';
    document.getElementById('newCategoryError').classList.add('hidden');
    document.getElementById('newCategoryModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('newCategoryInput').focus(), 50);
}

function closeNewCategoryModal() {
    document.getElementById('newCategoryModal').classList.add('hidden');
}

function submitNewCategory() {
    const input = document.getElementById('newCategoryInput');
    const errEl = document.getElementById('newCategoryError');
    const btn = document.getElementById('saveCategoryBtn');
    const name = input.value.trim();

    if (!name) {
        errEl.textContent = 'Category name is required.';
        errEl.classList.remove('hidden');
        return;
    }

    errEl.classList.add('hidden');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    fetch("{{ route('categories.store') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ Name: name })
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Save Category';

        if (data.success && data.category) {
            const select = document.getElementById('categorySelect');
            const opt = document.createElement('option');
            opt.value = data.category.id;
            opt.textContent = data.category.name;
            opt.selected = true;
            select.appendChild(opt);
            closeNewCategoryModal();
        } else {
            errEl.textContent = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Error creating category');
            errEl.classList.remove('hidden');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Save Category';
        errEl.textContent = 'Connection error. Please try again.';
        errEl.classList.remove('hidden');
    });
}
</script>

<!-- Modal for Creating New Category -->
<div id="newCategoryModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-folder-plus text-red-500"></i>
                Create New Category
            </h3>
            <button type="button" onclick="closeNewCategoryModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl leading-none">&times;</button>
        </div>

        <div class="space-y-3">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Category Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="newCategoryInput" placeholder="e.g. Roof Racks & Exterior"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                    onkeydown="if(event.key === 'Enter') { event.preventDefault(); submitNewCategory(); }">
                <p id="newCategoryError" class="text-xs text-rose-500 mt-1 hidden"></p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <button type="button" onclick="closeNewCategoryModal()" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-300 dark:hover:bg-dark-700">
                Cancel
            </button>
            <button type="button" id="saveCategoryBtn" onclick="submitNewCategory()" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-md shadow-red-600/25 flex items-center gap-1.5">
                <i class="fas fa-check"></i>
                <span>Save Category</span>
            </button>
        </div>
    </div>
</div>
@endsection