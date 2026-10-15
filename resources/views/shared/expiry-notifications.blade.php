<style>
    .expiry-trigger { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border: 1px solid #cbd5e1; border-radius: 12px; background: #e2e8f0; color: #475569; }
    .expiry-badge { position: absolute; right: -5px; top: -6px; min-width: 20px; border-radius: 12px; padding: 2px 5px; background: #dc2626; color: white; font-size: 10px; font-weight: 800; }
    .expiry-panel { position: fixed; top: 88px; right: 24px; z-index: 70; width: min(400px, calc(100vw - 32px)); max-height: 75vh; overflow: auto; border: 1px solid #cbd5e1; border-radius: 16px; background: white; color: #0f172a; box-shadow: 0 15px 45px #0003; }
    .expiry-heading { padding: 18px; border-bottom: 1px solid #e2e8f0; }
    .expiry-heading h2 { font-size: 17px; font-weight: 800; }
    .expiry-heading p, .expiry-meta, .expiry-summary { font-size: 12px; line-height: 1.5; color: #64748b; }
    .expiry-row { display: block; padding: 14px 18px; border-bottom: 1px solid #e2e8f0; }
    .expiry-row:hover { background: #fff7ed; }
    .expiry-product { display: block; font-size: 14px; font-weight: 700; overflow-wrap: anywhere; }
    .expiry-date { display: block; margin-top: 4px; font-size: 12px; font-weight: 700; color: #b45309; }
    .expiry-summary { padding: 16px 18px; }
    .expiry-panel[hidden], .expiry-badge[hidden] { display: none !important; }
    html.dark .expiry-trigger { background: #1e293b; color: #fbbf24; border-color: #334155; }
    html.dark .expiry-panel { background: #131926; color: #f8fafc; border-color: #334155; }
    html.dark .expiry-heading, html.dark .expiry-row { border-color: #334155; }
    html.dark .expiry-row:hover { background: #1e293b; }
    html.dark .expiry-heading p, html.dark .expiry-meta, html.dark .expiry-summary { color: #94a3b8; }
    html.dark .expiry-date { color: #fbbf24; }
    @media (max-width: 1023px) { .expiry-panel { top: 140px; right: 16px; } }
    @media print { .expiry-trigger, .expiry-panel { display: none !important; } }
</style>
<div id="expiryNotifications" data-url="{{ route('notifications.expiry') }}">
    <button type="button" id="expiryNotificationButton" class="expiry-trigger" aria-label="Expiry notifications: {{ $expiryAlerts['count'] }} batches" aria-expanded="false" aria-controls="expiryNotificationPanel" title="Items expiring within 30 days">
        <i class="fas fa-bell" aria-hidden="true"></i>
        <span id="expiryNotificationCount" class="expiry-badge" @if($expiryAlerts['count'] === 0) hidden @endif>{{ $expiryAlerts['count'] > 99 ? '99+' : $expiryAlerts['count'] }}</span>
    </button>
    <section id="expiryNotificationPanel" class="expiry-panel" aria-labelledby="expiryNotificationHeading" hidden>
        <div class="expiry-heading">
            <h2 id="expiryNotificationHeading" tabindex="-1">Expiring Within 30 Days</h2>
            <p>Remaining stock batches, based on the laptop's date.</p>
        </div>
        <div id="expiryNotificationItems">
            @foreach($expiryAlerts['items'] as $item)
                <a class="expiry-row" href="{{ $item['url'] }}">
                    <span class="expiry-product">{{ $item['product'] }}</span>
                    <span class="expiry-meta">{{ $item['batch'] }} &middot; {{ $item['quantity'] }} remaining</span>
                    <span class="expiry-date">{{ $item['days_remaining'] === 0 ? 'Expires today' : 'Expires in '.$item['days_remaining'].' days' }} &middot; {{ $item['expires_on'] }}</span>
                </a>
            @endforeach
        </div>
        <p id="expiryNotificationSummary" class="expiry-summary" role="status">{{ $expiryAlerts['count'] === 0 ? 'No remaining stock expires within the next 30 days.' : $expiryAlerts['count'].' batch(es) need attention. Showing the earliest '.count($expiryAlerts['items']).'.' }}</p>
    </section>
</div>
<script>
(() => {
    const root = document.getElementById('expiryNotifications');
    const button = document.getElementById('expiryNotificationButton');
    const panel = document.getElementById('expiryNotificationPanel');
    const count = document.getElementById('expiryNotificationCount');
    const items = document.getElementById('expiryNotificationItems');
    const summary = document.getElementById('expiryNotificationSummary');
    let refreshing = false;
    let stopped = false;
    function close() { panel.hidden = true; button.setAttribute('aria-expanded', 'false'); }
    button.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        button.setAttribute('aria-expanded', String(!panel.hidden));
        if (!panel.hidden) { document.getElementById('expiryNotificationHeading').focus(); refresh(); }
    });
    document.addEventListener('click', event => { if (!root.contains(event.target)) close(); });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) { close(); button.focus(); }
    });
    function text(tag, className, value) {
        const element = document.createElement(tag);
        element.className = className;
        element.textContent = value;
        return element;
    }
    async function refresh() {
        if (refreshing || stopped || document.hidden) return;
        refreshing = true;
        try {
            const response = await fetch(root.dataset.url, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
            if (response.status === 401 || response.status === 403) { stopped = true; return; }
            if (!response.ok) return;
            const data = await response.json();
            if (!Number.isInteger(data.count) || !Array.isArray(data.items)) return;
            count.hidden = data.count === 0;
            count.textContent = data.count > 99 ? '99+' : data.count;
            button.setAttribute('aria-label', `Expiry notifications: ${data.count} batches`);
            const rows = document.createDocumentFragment();
            data.items.forEach(item => {
                const link = document.createElement('a');
                link.className = 'expiry-row';
                link.href = item.url;
                link.append(text('span', 'expiry-product', item.product));
                link.append(text('span', 'expiry-meta', `${item.batch} · ${item.quantity} remaining`));
                link.append(text('span', 'expiry-date', `${item.days_remaining === 0 ? 'Expires today' : `Expires in ${item.days_remaining} days`} · ${item.expires_on}`));
                rows.append(link);
            });
            items.replaceChildren(rows);
            summary.textContent = data.count === 0
                ? 'No remaining stock expires within the next 30 days.'
                : `${data.count} batch(es) need attention. Showing the earliest ${data.items.length}.`;
        } catch (_) { /* Retain the last valid notification list if a refresh fails. */ }
        finally { refreshing = false; }
    }
    window.setInterval(refresh, 30000);
    window.addEventListener('focus', refresh);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
</script>
