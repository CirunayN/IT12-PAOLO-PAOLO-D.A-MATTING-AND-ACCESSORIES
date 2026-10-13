<div class="rounded-2xl p-5 border border-amber-500/30 bg-amber-500/10 space-y-3">
    <h2 class="font-bold text-amber-500">Save Your Recovery Code</h2>
    <p class="text-xs text-slate-500 dark:text-slate-400">Account: <strong>{{ $recovery['username'] }}</strong>. This code is shown only once. Save it somewhere private before leaving this page.</p>
    <div class="rounded-xl p-3 bg-white dark:bg-slate-900 border border-amber-500/30 font-mono font-bold text-center text-slate-900 dark:text-white select-all break-all">{{ $recovery['code'] }}</div>
    <p class="text-xs text-slate-500 dark:text-slate-400">Use this code with your username to reset a forgotten password. It works offline and can reset the password only once. Generating a replacement invalidates the previous code.</p>
    <button type="button" data-recovery-code="{{ $recovery['code'] }}" data-username="{{ $recovery['username'] }}"
        onclick="saveAccountRecoveryCode(this)" class="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold">
        <i class="fas fa-download mr-1"></i> Save Code (.txt)
    </button>
</div>
@once
<script>
function saveAccountRecoveryCode(button) {
    const username = button.dataset.username;
    const text = 'Paolo Paolo D.A. Matting & Accessories\nAccount: ' + username
        + '\nRecovery code: ' + button.dataset.recoveryCode
        + '\n\nKeep this private. Use it once at Forgot password to reset your password offline.\nA replacement code invalidates this code.\n';
    const url = URL.createObjectURL(new Blob([text], {type: 'text/plain;charset=utf-8'}));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'paolo-recovery-' + username.replace(/[^a-z0-9_-]/ig, '_') + '.txt';
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}
</script>
@endonce
