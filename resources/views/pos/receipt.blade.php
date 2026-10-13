<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt #{{ $sale->ID }} | Paolo Paolo</title>
    <style>
        @page { size: auto; margin: 0; }
        * { box-sizing: border-box; }
        body {
            --paper-width: 80mm;
            --side-padding: 4mm;
            --receipt-font-size: 9pt;
            margin: 0;
            padding: 20px;
            background: #eef0f3;
            color: #000;
        }
        body[data-paper-width="58"] {
            --paper-width: 58mm;
            --side-padding: 5mm;
            --receipt-font-size: 8pt;
        }
        .print-controls {
            max-width: 540px;
            margin: 0 auto 20px;
            font: 14px/1.5 Arial, sans-serif;
            text-align: center;
        }
        .print-controls label { font-weight: bold; }
        .print-controls select, .print-controls button {
            margin: 4px;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font: inherit;
            background: #fff;
            color: #000;
        }
        .print-controls button { cursor: pointer; }
        .print-controls .print-button { background: #dc2626; color: #fff; border-color: #dc2626; font-weight: bold; }
        .print-controls p { margin: 8px 0 0; color: #475569; font-size: 12px; }
        .receipt-paper {
            width: var(--paper-width);
            margin: 0 auto;
            padding: 3mm var(--side-padding) 4mm;
            background: #fff;
            font: var(--receipt-font-size)/1.3 'Courier New', Courier, monospace;
            box-shadow: 0 2px 12px #0002;
        }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .business-name { font-size: 12pt; letter-spacing: .3px; }
        .small { font-size: 8pt; }
        body[data-paper-width="58"] .business-name { font-size: 10pt; }
        body[data-paper-width="58"] .small { font-size: 7pt; }
        .divider { border: 0; border-top: 1px dashed #000; margin: 2mm 0; }
        .row { display: table; width: 100%; table-layout: fixed; }
        .row > span { display: table-cell; vertical-align: top; overflow-wrap: anywhere; }
        .row > span:first-child { width: 42%; padding-right: 2mm; }
        .row > span:last-child { text-align: right; }
        .item { margin: 0 0 2mm; }
        .item-name { overflow-wrap: anywhere; }
        .item .row > span:first-child { width: 58%; }
        .total { font-size: 11pt; }
        body[data-paper-width="58"] .total { font-size: 9pt; }
        .totals .row { margin-bottom: 1mm; }
        .sale-details, .item, .totals, .receipt-footer {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .receipt-footer { overflow-wrap: anywhere; }
        .receipt-footer > div + div { margin-top: 1mm; }
        @media print {
            html, body { margin: 0 !important; padding: 0 !important; background: #fff; }
            body { width: var(--paper-width); }
            .no-print { display: none !important; }
            .receipt-paper { margin: 0; box-shadow: none; }
        }
    </style>
    <style id="thermalPageSize"></style>
</head>
<body data-paper-width="80">
    <div class="print-controls no-print">
        <label for="paperWidth">Thermal paper:</label>
        <select id="paperWidth" onchange="setReceiptWidth(this.value)">
            <option value="80">80 mm</option>
            <option value="58">58 mm</option>
        </select>
        <button type="button" class="print-button" onclick="printReceipt()">Print Receipt</button>
        <button type="button" onclick="window.close()">Close</button>
        <p>Choose your thermal printer and matching paper width in the print dialog. Use 100% scale, no margins, and turn off headers and footers.</p>
    </div>

    <main id="thermalReceipt" class="receipt-paper" aria-label="Sales receipt">
        <header class="text-center">
            <div class="bold business-name">PAOLO PAOLO</div>
            <div class="bold">D.A MATTING &amp; ACCESSORIES</div>
            <div class="small">Car Accessories &bull; Custom Deep Dish Matting</div>
            <div class="small">Contact: 09267994701 / 09105508162</div>
        </header>

        <hr class="divider">

        <section class="sale-details" aria-label="Transaction details">
            <div class="row"><span>Invoice #:</span><span class="bold">INV-{{ str_pad($sale->ID, 6, '0', STR_PAD_LEFT) }}</span></div>
            <div class="row"><span>Date:</span><span>{{ $sale->Date ? $sale->Date->format('M d, Y h:i A') : $sale->created_at->format('M d, Y h:i A') }}</span></div>
            <div class="row"><span>Cashier:</span><span>{{ $sale->user->name ?? 'Staff' }} ({{ $sale->user->role ?? 'Staff' }})</span></div>
            <div class="row"><span>Payment:</span><span class="bold">{{ $sale->paymentMethod->Name ?? 'Cash' }}</span></div>
            @if(strtolower($sale->paymentMethod->Name ?? '') === 'gcash' && $sale->GCash_Reference_Number)
                <div class="row"><span>GCash Ref:</span><span class="bold">{{ $sale->GCash_Reference_Number }}</span></div>
            @endif
        </section>

        <hr class="divider">

        <div class="bold" style="margin-bottom: 1mm;">ITEMS PURCHASED</div>
        @foreach($sale->soldItems as $item)
            @php
                $quantity = (float) $item->Quantity;
                $quantityText = rtrim(rtrim(number_format($quantity, 2), '0'), '.');
                $unitPrice = $quantity > 0 ? round((float) $item->Total / $quantity, 2) : 0;
            @endphp
            <div class="item">
                <div class="bold item-name">{{ $item->product->Name ?? 'Product' }}</div>
                <div class="row small">
                    <span>{{ $quantityText }}x @ ₱{{ number_format($unitPrice, 2) }}</span>
                    <span class="bold">₱{{ number_format($item->Total, 2) }}</span>
                </div>
            </div>
        @endforeach

        <hr class="divider">

        <section class="totals" aria-label="Payment totals">
            <div class="row total bold"><span>TOTAL:</span><span>₱{{ number_format($sale->Total, 2) }}</span></div>
            @if(strtolower($sale->paymentMethod->Name ?? '') === 'cash')
                <div class="row"><span>Amount Received:</span><span>₱{{ number_format($sale->Amount_Received, 2) }}</span></div>
                <div class="row"><span>Change:</span><span>₱{{ number_format($sale->Change_Amount, 2) }}</span></div>
            @endif
        </section>

        <hr class="divider">

        <footer class="text-center receipt-footer small">
            <div class="bold">THANK YOU FOR YOUR PURCHASE!</div>
            <div>Please keep this receipt for warranty claims.</div>
            <div>D.A Matting &amp; Accessories - Japanese Quality</div>
        </footer>
    </main>

    <script>
        function updateThermalPageSize() {
            const width = document.body.dataset.paperWidth === '58' ? 58 : 80;
            // CSS page sizes require both dimensions; exclude the screen-only controls.
            const height = Math.ceil(document.getElementById('thermalReceipt').getBoundingClientRect().height * 25.4 / 96) + 2;
            document.getElementById('thermalPageSize').textContent = '@page { size: ' + width + 'mm ' + height + 'mm; margin: 0; }';
        }

        function setReceiptWidth(value) {
            const width = value === '58' ? '58' : '80';
            document.body.dataset.paperWidth = width;
            document.getElementById('paperWidth').value = width;
            try { localStorage.setItem('paolo_receipt_paper_width', width); } catch (_) {}
            updateThermalPageSize();
        }

        function printReceipt() {
            updateThermalPageSize();
            window.print();
        }

        let savedWidth = '80';
        try { savedWidth = localStorage.getItem('paolo_receipt_paper_width') || '80'; } catch (_) {}
        setReceiptWidth(savedWidth);
        window.addEventListener('beforeprint', updateThermalPageSize);
        window.addEventListener('load', printReceipt);
    </script>
</body>
</html>
