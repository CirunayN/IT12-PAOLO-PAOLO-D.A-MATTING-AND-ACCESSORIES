@extends('shared.table-report')
@section('title', 'Stock Receiving Log')
@section('report')
<p>{{ $stockIns->count() }} batches matching selected filters</p>
<table><thead><tr><th>Batch / Received</th><th>Product</th><th>Processed by</th><th>Received / Remaining</th><th>Unit cost / Retail</th><th>Expiration</th><th>Condition</th></tr></thead><tbody>
@forelse($stockIns as $batch)
<tr><td>#SI-{{ $batch->ID }}<br>{{ $batch->created_at?->format('M d, Y h:i A') }}</td><td>{{ $batch->product?->Name }}</td><td>{{ $batch->user?->name }}</td><td class="number">{{ number_format($batch->Quantity, 2) }} / {{ number_format($batch->Remaining_Quantity, 2) }}</td><td class="number">₱{{ number_format($batch->Cost_Price, 2) }} / ₱{{ number_format($batch->Retail_Price, 2) }}</td><td>{{ $batch->Has_Expiration ? $batch->Expiration_Date?->format('M d, Y') : 'No expiration' }}</td><td>{{ $batch->Condition }}</td></tr>
@empty<tr><td colspan="7">No batches match the selected filters.</td></tr>@endforelse
</tbody></table>
@endsection
