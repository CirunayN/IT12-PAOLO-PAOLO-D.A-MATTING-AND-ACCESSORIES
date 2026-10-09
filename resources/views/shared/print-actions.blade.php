@unless($downloadPdf ?? false)
<div class="no-print" style="padding:12px;margin-bottom:16px;background:#f1f5f9;display:flex;gap:12px;justify-content:flex-end">
    <a href="{{ request()->fullUrlWithQuery(['output' => 'pdf']) }}" style="background:#dc2626;color:white;padding:10px 16px;border-radius:6px;text-decoration:none">Download PDF</a>
    <button type="button" onclick="window.print()" style="background:#1e293b;color:white;padding:10px 16px;border:0;border-radius:6px;cursor:pointer">Print</button>
</div>
@if(request('output') === 'print')<script>window.addEventListener('load', () => window.print());</script>@endif
@endunless
