@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-boxes-stacked text-red-500"></i>
                Inventory
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('products.create') }}" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center gap-2 transition-all">
                <i class="fas fa-plus"></i>
                <span>Add Product</span>
            </a>
            <a href="{{ route('stock-in.create') }}" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-800 dark:text-slate-100 font-bold text-sm border border-slate-300 dark:border-slate-700 flex items-center gap-2 transition-all">
                <i class="fas fa-truck-ramp-box text-red-500"></i>
                <span>Receive Stock</span>
            </a>
        </div>
    </div>

    <!-- Status Tabs: Active vs Archived -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <a href="{{ route('products.index', ['tab' => 'active']) }}" 
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 {{ $tab === 'active' ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700' }}">
            <i class="fas fa-box-check"></i>
            <span>Active Catalog</span>
            <span class="px-2 py-0.5 rounded-full text-xs {{ $tab === 'active' ? 'bg-white/20 text-white' : 'bg-slate-300 dark:bg-dark-700 text-slate-700 dark:text-slate-200' }}">{{ $activeCount }}</span>
        </a>

        <a href="{{ route('products.index', ['tab' => 'archived']) }}" 
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 {{ $tab === 'archived' ? 'bg-amber-500 text-slate-900 shadow-sm' : 'bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700' }}">
            <i class="fas fa-box-archive"></i>
            <span>Archived / Disabled</span>
            <span class="px-2 py-0.5 rounded-full text-xs {{ $tab === 'archived' ? 'bg-black/20 text-slate-900' : 'bg-slate-300 dark:bg-dark-700 text-slate-700 dark:text-slate-200' }}">{{ $archivedCount }}</span>
        </a>

        
        <a href="{{ route('stock-in.index') }}" 
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700">
            <i class="fas fa-truck-ramp-box text-red-500"></i>
            <span>Stock-In Receiving Logs</span>
        </a>

        <a href="{{ route('products.index', ['tab' => 'all']) }}" 
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 {{ $tab === 'all' ? 'bg-slate-800 text-white shadow-sm dark:bg-dark-700' : 'bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700' }}">
            <span>All Items</span>
            <span class="px-2 py-0.5 rounded-full text-xs {{ $tab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-300 dark:bg-dark-700 text-slate-700 dark:text-slate-200' }}">{{ $totalCount }}</span>
        </a>
    </div>

    <!-- Filter & Search Bar -->
    <div class="glass-card rounded-2xl p-4 border shadow-sm">
        <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="sm:col-span-6 relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by product name..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div class="sm:col-span-4">
                <select name="category_id" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->ID }}" {{ request('category_id') == $cat->ID ? 'selected' : '' }}>{{ $cat->Name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 dark:bg-dark-700 dark:hover:bg-dark-600 text-white font-bold text-sm">
                    Filter
                </button>
                <a href="{{ route('products.index', ['tab' => $tab]) }}" class="px-3 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-600 dark:text-slate-300 text-sm">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="glass-card rounded-2xl border shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-dark-850 border-b border-slate-200 dark:border-slate-800">
                        <th class="p-4">Item</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Stock On-Hand</th>
                        <th class="p-4 text-right">Retail Price</th>
                        <th class="p-4 text-right">Cost</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($products as $product)
                    @php
                        $stock = $product->stock_quantity;
                        $retail = $product->retail_price;
                        $cost = $product->cost_price;
                        $isArchived = ($product->status && $product->status->Name === 'Archived');
                        $allImages = $product->all_image_urls;
                        $imagesCount = count($allImages);
                        $imagesJson = json_encode($allImages);
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors {{ $isArchived ? 'opacity-60 bg-slate-50/50 dark:bg-dark-900/30' : '' }}">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <!-- Clickable Thumbnail Box to Open Expanded Lightbox -->
                                <div class="relative w-14 h-14 rounded-2xl bg-slate-100 dark:bg-dark-800 border border-slate-200 dark:border-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center cursor-pointer group"
                                    onclick="openImageGallery({{ $imagesJson }}, '{{ addslashes($product->Name) }}')"
                                    title="Click to view all photos in gallery">
                                    @if($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->Name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    @else
                                        <i class="fas fa-box text-slate-400 text-base group-hover:text-red-500 transition-colors"></i>
                                    @endif

                                    <!-- Multi-photo count badge -->
                                    @if($imagesCount > 1)
                                    <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-md bg-black/75 backdrop-blur-sm text-white text-[9px] font-black leading-none shadow-sm flex items-center gap-0.5">
                                        <i class="fas fa-images text-[8px]"></i> {{ $imagesCount }}
                                    </span>
                                    @endif

                                    <!-- Hover zoom icon overlay -->
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs transition-opacity">
                                        <i class="fas fa-magnifying-glass-plus"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <span class="cursor-pointer hover:text-red-500 transition-colors" onclick="openImageGallery({{ $imagesJson }}, '{{ addslashes($product->Name) }}')">{{ $product->Name }}</span>
                                        @if($isArchived)
                                        <span class="text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-500 border border-amber-500/30">Archived</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-400 font-mono">#{{ $product->ID }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-500/10 text-red-500 border border-red-500/20">
                                {{ $product->category->Name ?? 'General' }}
                            </span>
                        </td>
                        <td class="p-4">
                            @if($isArchived)
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-500/15 text-amber-500 border border-amber-500/30">
                                <i class="fas fa-box-archive mr-1 text-[10px]"></i> Archived
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $product->status && $product->status->Name === 'Active' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                {{ $product->status->Name ?? 'Active' }}
                            </span>
                            @endif
                        </td>
                        <td class="p-4 text-right font-black">
                            @if($stock <= 0)
                            <span class="text-rose-500 font-extrabold">0 Units</span>
                            @elseif($stock <= 5)
                            <span class="text-amber-500 font-extrabold">{{ $stock }} Units</span>
                            @else
                            <span class="text-emerald-500 font-extrabold">{{ $stock }} Units</span>
                            @endif
                        </td>
                        <td class="p-4 text-right font-black font-display text-slate-900 dark:text-white">
                            ₱{{ number_format($retail, 2) }}
                        </td>
                        <td class="p-4 text-right text-slate-500 dark:text-slate-400">
                            ₱{{ number_format($cost, 2) }}
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if(!$isArchived)
                                <a href="{{ route('products.edit', $product->ID) }}" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-red-600 hover:text-white flex items-center justify-center text-xs transition-colors" title="Edit Product">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <button type="button" 
                                    onclick="openArchiveModal('{{ $product->ID }}', '{{ addslashes($product->Name) }}')"
                                    class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-rose-500 hover:text-white text-slate-400 flex items-center justify-center text-xs transition-colors" 
                                    title="Archive / Disable Product">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                                @else
                                <form method="POST" action="{{ route('products.restore', $product->ID) }}" class="inline-block">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500/15 hover:bg-emerald-500 text-emerald-600 dark:text-emerald-400 hover:text-white border border-emerald-500/30 text-xs font-bold transition-colors flex items-center gap-1.5" title="Reactivate this product">
                                        <i class="fas fa-rotate-left text-[11px]"></i>
                                        <span>Restore</span>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">
                            No products found in this view.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>

<!-- EXPANDED MULTI-IMAGE GALLERY LIGHTBOX MODAL (Like P8) -->
<div id="galleryModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md hidden items-center justify-center p-4">
    <div class="w-full max-w-2xl bg-white dark:bg-[#0c1222] border border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <h3 id="galleryTitle" class="text-base font-bold font-display text-slate-900 dark:text-white truncate"></h3>
                <span id="galleryCounter" class="text-xs px-2 py-0.5 rounded-full bg-red-500/15 text-red-500 font-bold"></span>
            </div>
            <button type="button" onclick="closeImageGallery()" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl flex items-center justify-center cursor-pointer">&times;</button>
        </div>

        <!-- Main Display Photo -->
        <div class="relative bg-slate-100 dark:bg-dark-900 rounded-2xl h-80 flex items-center justify-center p-4 overflow-hidden">
            <img id="galleryMainImg" src="" class="max-h-full max-w-full object-contain drop-shadow-xl transition-all duration-300">

            <!-- Prev / Next Navigation Arrows -->
            <button type="button" id="prevGalleryBtn" onclick="prevGalleryImage()" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center shadow-lg transition-transform hover:scale-110 cursor-pointer">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" id="nextGalleryBtn" onclick="nextGalleryImage()" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center shadow-lg transition-transform hover:scale-110 cursor-pointer">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <!-- Thumbnails Strip -->
        <div class="flex items-center justify-center gap-2.5 pt-1 overflow-x-auto" id="galleryThumbnailsStrip"></div>
    </div>
</div>

<!-- ARCHIVE / DELETE CONFIRMATION MODAL -->
<div id="archiveModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
        <div class="text-center pb-2">
            <div class="w-12 h-12 rounded-full bg-amber-500/15 text-amber-500 mx-auto flex items-center justify-center text-2xl mb-3">
                <i class="fas fa-box-archive"></i>
            </div>
            <h3 class="font-display font-black text-xl text-slate-900 dark:text-white">Archive Product</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Disable this product without losing historical sales or stock records.
            </p>
        </div>

        <div class="p-4 rounded-xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300 space-y-2">
            <div>Are you sure you want to archive:</div>
            <div class="font-bold text-sm text-slate-900 dark:text-white" id="modalProductName">—</div>
            <div class="text-[11px] text-slate-400 pt-1 border-t border-slate-200 dark:border-slate-800">
                <i class="fas fa-info-circle text-red-500 mr-1"></i>
                This will immediately hide the item from the POS cashier terminal. You can restore it anytime from the <strong>Archived</strong> tab.
            </div>
        </div>

        <form id="archiveForm" method="POST" action="" class="flex items-center justify-end gap-3 pt-2">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeArchiveModal()" class="px-4 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300 font-bold text-xs">
                Cancel
            </button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-bold text-xs shadow-md shadow-rose-500/20 flex items-center gap-1.5">
                <i class="fas fa-box-archive"></i>
                <span>Yes, Archive Product</span>
            </button>
        </form>
    </div>
</div>

<script>
// Lightbox Gallery Logic
let currentGalleryImages = [];
let currentGalleryIndex = 0;

function openImageGallery(images, title) {
    if (!images || images.length === 0) return;

    currentGalleryImages = images;
    currentGalleryIndex = 0;
    document.getElementById('galleryTitle').innerText = title;
    updateGalleryDisplay();

    const modal = document.getElementById('galleryModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeImageGallery() {
    const modal = document.getElementById('galleryModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function updateGalleryDisplay() {
    if (!currentGalleryImages || currentGalleryImages.length === 0) return;

    document.getElementById('galleryMainImg').src = currentGalleryImages[currentGalleryIndex];
    document.getElementById('galleryCounter').innerText = `${currentGalleryIndex + 1} / ${currentGalleryImages.length}`;

    const prevBtn = document.getElementById('prevGalleryBtn');
    const nextBtn = document.getElementById('nextGalleryBtn');
    if (currentGalleryImages.length <= 1) {
        prevBtn.classList.add('hidden');
        nextBtn.classList.add('hidden');
    } else {
        prevBtn.classList.remove('hidden');
        nextBtn.classList.remove('hidden');
    }

    const strip = document.getElementById('galleryThumbnailsStrip');
    strip.innerHTML = '';

    currentGalleryImages.forEach((url, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `w-14 h-14 rounded-xl border-2 overflow-hidden transition-all flex-shrink-0 cursor-pointer ${i === currentGalleryIndex ? 'border-red-500 scale-105 shadow-md shadow-red-600/30' : 'border-slate-300 dark:border-slate-700 opacity-60 hover:opacity-100'}`;
        btn.innerHTML = `<img src="${url}" class="w-full h-full object-cover">`;
        btn.onclick = () => {
            currentGalleryIndex = i;
            updateGalleryDisplay();
        };
        strip.appendChild(btn);
    });
}

function prevGalleryImage() {
    if (currentGalleryIndex > 0) {
        currentGalleryIndex--;
    } else {
        currentGalleryIndex = currentGalleryImages.length - 1;
    }
    updateGalleryDisplay();
}

function nextGalleryImage() {
    if (currentGalleryIndex < currentGalleryImages.length - 1) {
        currentGalleryIndex++;
    } else {
        currentGalleryIndex = 0;
    }
    updateGalleryDisplay();
}

// Archive Modal Logic
function openArchiveModal(productId, productName) {
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('archiveForm').action = "{{ url('products') }}/" + productId;
    document.getElementById('archiveModal').classList.remove('hidden');
}

function closeArchiveModal() {
    document.getElementById('archiveModal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeArchiveModal();
        closeImageGallery();
    } else if (e.key === 'ArrowLeft') {
        if (!document.getElementById('galleryModal').classList.contains('hidden')) {
            prevGalleryImage();
        }
    } else if (e.key === 'ArrowRight') {
        if (!document.getElementById('galleryModal').classList.contains('hidden')) {
            nextGalleryImage();
        }
    }
});
</script>
@endsection