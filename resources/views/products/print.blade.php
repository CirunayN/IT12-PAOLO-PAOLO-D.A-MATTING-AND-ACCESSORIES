@extends('shared.table-report')
@section('title', 'Inventory')
@section('report')
<p>{{ ucfirst($tab) }} inventory · {{ $products->count() }} items matching selected filters</p>
<table><thead><tr><th>Product</th><th>Category</th><th>Status</th><th>Sellable stock</th><th>Stock status</th><th>Unit cost</th><th>Retail price</th></tr></thead><tbody>
@forelse($products as $product)
@php($quantity = $product->stock_quantity)
<tr><td>{{ $product->Name }}</td><td>{{ $product->category?->Name }}</td><td>{{ $product->status?->Name }}</td><td class="number">{{ number_format($quantity, 2) }}</td><td>{{ $quantity <= 0 ? 'Out of stock' : ($quantity <= 5 ? 'Low on stock' : 'Available') }}</td><td class="number">₱{{ number_format($product->cost_price, 2) }}</td><td class="number">₱{{ number_format($product->retail_price, 2) }}</td></tr>
@empty<tr><td colspan="7">No items match the selected filters.</td></tr>@endforelse
</tbody></table>
@endsection
