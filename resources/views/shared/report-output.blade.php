<div id="reportOutputDialog" role="dialog" aria-modal="true" aria-labelledby="reportOutputTitle" class="no-print hidden fixed inset-0 z-[100] bg-black/60 items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-dark-850 border border-slate-200 dark:border-slate-700 p-6 space-y-4 shadow-xl">
        <h2 id="reportOutputTitle" class="text-lg font-bold text-slate-900 dark:text-white">Report output</h2>
        <p class="text-sm text-slate-500">Choose how to save or print this report.</p>
        <button type="button" onclick="chooseReportOutput('pdf')" class="w-full rounded-xl px-4 py-3 bg-red-600 text-white font-bold"><i class="fas fa-file-pdf mr-2"></i> Download PDF</button>
        <button type="button" onclick="chooseReportOutput('print')" class="w-full rounded-xl px-4 py-3 bg-slate-800 text-white font-bold"><i class="fas fa-print mr-2"></i> Print</button>
        <button type="button" onclick="closeReportOutput()" class="w-full text-sm text-slate-500">Cancel</button>
    </div>
</div>
<script>
let requestedReportUrl, requestedReportAction;
function openReportOutput(url, action = null) {
    requestedReportUrl = url;
    requestedReportAction = action;
    const dialog = document.getElementById('reportOutputDialog');
    dialog.classList.remove('hidden'); dialog.classList.add('flex');
    dialog.querySelector('button').focus();
}
function closeReportOutput() {
    const dialog = document.getElementById('reportOutputDialog');
    dialog.classList.add('hidden'); dialog.classList.remove('flex');
}
function chooseReportOutput(output) {
    if (requestedReportAction) { const action = requestedReportAction; closeReportOutput(); action(output); return; }
    const url = new URL(requestedReportUrl, location.origin);
    url.searchParams.set('output', output);
    if (output === 'print') window.open(url.href, '_blank', 'noopener');
    else location.assign(url.href);
    closeReportOutput();
}
document.getElementById('reportOutputDialog').addEventListener('click', event => { if (event.target.id === 'reportOutputDialog') closeReportOutput(); });
document.addEventListener('keydown', event => { if (event.key === 'Escape') closeReportOutput(); });
</script>
