<section class="space-y-3" id="productBatchEditor">
    <div>
        <h2 class="font-bold text-slate-900 dark:text-white">Stock batches</h2>
        <p class="text-xs text-slate-500 mt-1">Edit received quantity, prices, expiration and condition. Quantities already sold stay recorded; the remaining stock updates automatically.</p>
    </div>
    @forelse($product->stockIns as $batch)
    @php
        $used = max(0, round((float) $batch->Quantity - (float) $batch->Remaining_Quantity, 2));
        $hasExpiration = (bool) old('batches.'.$batch->ID.'.Has_Expiration', $batch->Has_Expiration);
        $inputClass = 'w-full mt-1 px-3 py-2 rounded-xl bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white';
    @endphp
    <details class="rounded-xl border border-slate-200 dark:border-slate-700 p-4" data-batch-editor data-used="{{ $used }}" {{ $loop->first || $errors->any() ? 'open' : '' }}>
        <summary class="cursor-pointer font-bold text-sm">Batch #SI-{{ $batch->ID }} · {{ $batch->created_at?->format('M d, Y') }} · {{ $batch->user?->name ?? 'Unknown' }}</summary>
        <input type="hidden" name="batches[{{ $batch->ID }}][ID]" value="{{ $batch->ID }}">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
            <label class="text-xs font-semibold">Quantity received
                <input class="{{ $inputClass }}" data-batch-quantity type="number" min="{{ $used }}" step="0.01" required name="batches[{{ $batch->ID }}][Quantity]" value="{{ old('batches.'.$batch->ID.'.Quantity', $batch->Quantity) }}">
            </label>
            <label class="text-xs font-semibold">Unit cost (₱)
                <input class="{{ $inputClass }}" type="number" min="0" max="99999999.99" step="0.01" required name="batches[{{ $batch->ID }}][Cost_Price]" value="{{ old('batches.'.$batch->ID.'.Cost_Price', $batch->Cost_Price) }}">
            </label>
            <label class="text-xs font-semibold">Retail price (₱)
                <input class="{{ $inputClass }}" type="number" min="0" max="99999999.99" step="0.01" required name="batches[{{ $batch->ID }}][Retail_Price]" value="{{ old('batches.'.$batch->ID.'.Retail_Price', $batch->Retail_Price) }}">
            </label>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
            <div>
                <input type="hidden" name="batches[{{ $batch->ID }}][Has_Expiration]" value="0">
                <label class="text-xs font-semibold flex gap-2 items-center">
                    <input data-batch-expiration-toggle type="checkbox" name="batches[{{ $batch->ID }}][Has_Expiration]" value="1" @checked($hasExpiration)> Has expiration date
                </label>
                <input aria-label="Batch expiration date" data-batch-expiration-date class="{{ $inputClass }} disabled:opacity-50" type="date" name="batches[{{ $batch->ID }}][Expiration_Date]" value="{{ old('batches.'.$batch->ID.'.Expiration_Date', $batch->Expiration_Date?->format('Y-m-d')) }}" @disabled(!$hasExpiration) @required($hasExpiration)>
            </div>
            <label class="text-xs font-semibold">Condition
                <select class="{{ $inputClass }}" required name="batches[{{ $batch->ID }}][Condition]">
                    @foreach(['Good', 'Damaged', 'Defective'] as $condition)
                        <option value="{{ $condition }}" @selected(old('batches.'.$batch->ID.'.Condition', $batch->Condition) === $condition)>{{ $condition }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <p class="text-xs text-slate-500 mt-3">Already used: {{ number_format($used, 2) }} · Remaining after save: <strong data-batch-remaining>{{ number_format((float) old('batches.'.$batch->ID.'.Quantity', $batch->Quantity) - $used, 2) }}</strong></p>
    </details>
    @empty
    <p class="text-sm text-slate-500">No batches yet. Receive a shipment to add stock.</p>
    @endforelse
</section>
@push('scripts')
<script>
document.querySelectorAll('[data-batch-editor]').forEach(batch => {
    const toggle = batch.querySelector('[data-batch-expiration-toggle]');
    const date = batch.querySelector('[data-batch-expiration-date]');
    toggle.addEventListener('change', () => { date.disabled = !toggle.checked; date.required = toggle.checked; });
    batch.querySelector('[data-batch-quantity]').addEventListener('input', event => {
        batch.querySelector('[data-batch-remaining]').textContent = (Number(event.target.value) - Number(batch.dataset.used)).toFixed(2);
    });
});
</script>
@endpush
