<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $sale->ID }} | Paolo Paolo</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            width: 72mm;
            margin: 0 auto;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 10px; text-align: center;">
        <button onclick="window.print()" style="padding: 6px 14px; cursor: pointer; font-weight: bold; background: #dc2626; color: #fff; border: none; border-radius: 6px;">Print Receipt</button>
        <button onclick="window.close()" style="padding: 6px 14px; cursor: pointer; border-radius: 6px;">Close</button>
    </div>

    <div class="text-center">
        <img src="{{ asset('images/wadwad_paolo_logo.png') }}" style="width: 60px; height: 60px; margin: 0 auto 3px auto; display: block;">
        <div class="bold" style="font-size: 15px; letter-spacing: 0.5px;">PAOLO PAOLO</div>
        <div class="bold" style="font-size: 11px;">D.A MATTING &amp; ACCESSORIES</div>
        <div style="font-size: 10px; margin-top: 2px;">Car Accessories &bull; Custom Deep Dish Matting</div>
        <div style="font-size: 10px;">Contact: 09267994701 / 09105508162</div>
    </div>

    <div class="divider"></div>

    <div class="row"><span>Invoice #:</span><span class="bold">INV-{{ str_pad($sale->ID, 6, '0', STR_PAD_LEFT) }}</span></div>
    <div class="row"><span>Date:</span><span>{{ $sale->Date ? $sale->Date->format('M d, Y h:i A') : $sale->created_at->format('M d, Y h:i A') }}</span></div>
    <div class="row"><span>Cashier:</span><span>{{ $sale->user->name ?? 'Staff' }} ({{ $sale->user->role ?? 'Staff' }})</span></div>
    <div class="row"><span>Payment:</span><span class="bold">{{ $sale->paymentMethod->Name ?? 'Cash' }}</span></div>

    <div class="divider"></div>

    <!-- Items Sold -->
    <div class="bold" style="margin-bottom: 4px;">ITEMS PURCHASED</div>
    @foreach($sale->soldItems as $item)
    <div style="margin-bottom: 4px;">
        <div class="bold">{{ $item->product->Name ?? 'Product' }}</div>
        <div class="row" style="font-size: 11px;">
            <span>{{ number_format($item->Quantity, 0) }}x @ ₱{{ number_format($item->product ? $item->product->retail_price : 0, 2) }}</span>
            <span class="bold">₱{{ number_format($item->Total, 2) }}</span>
        </div>
    </div>
    @endforeach

    <div class="divider"></div>

    <div class="row" style="font-size: 14px;"><span class="bold">TOTAL:</span><span class="bold">₱{{ number_format($sale->Total, 2) }}</span></div>

    <div class="divider"></div>

    <div class="text-center" style="font-size: 11px; margin-top: 8px;">
        <div class="bold">THANK YOU FOR YOUR PURCHASE!</div>
        <div style="font-size: 10px; color: #444; margin-top: 2px;">Please keep this receipt for warranty claims.</div>
        <div style="font-size: 9px; margin-top: 4px;">D.A Matting &amp; Accessories - Japanese Quality</div>
    </div>
</body>
</html>