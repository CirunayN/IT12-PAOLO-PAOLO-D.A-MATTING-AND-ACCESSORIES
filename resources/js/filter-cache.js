export class FilterMemory {
    constructor(now = () => Date.now(), limit = 20, lifetime = 15000) {
        this.now = now; this.limit = limit; this.lifetime = lifetime; this.entries = new Map();
    }
    get(key) {
        const entry = this.entries.get(key);
        if (!entry || entry.expires <= this.now()) { this.entries.delete(key); return null; }
        this.entries.delete(key); this.entries.set(key, entry);
        return entry.value;
    }
    put(key, value) {
        this.entries.delete(key); this.entries.set(key, {value, expires: this.now() + this.lifetime});
        while (this.entries.size > this.limit) this.entries.delete(this.entries.keys().next().value);
    }
    clear() { this.entries.clear(); }
}

export function initializeAutomaticFilters() {
    const memory = new FilterMemory();
    const canonical = value => {
        const url = new URL(value, location.href);
        for (const [name, value] of [...url.searchParams]) if (value === '') url.searchParams.delete(name);
        url.searchParams.sort(); url.hash = ''; return url.href;
    };
    const invalidate = () => memory.clear();
    const broadcast = () => {
        invalidate();
        try { localStorage.setItem('paolo-filter-change', `${Date.now()}-${Math.random()}`); } catch (_) { /* Storage may be disabled. */ }
    };
    window.addEventListener('focus', invalidate);
    window.addEventListener('storage', event => { if (event.key === 'paolo-filter-change') invalidate(); });
    window.addEventListener('paolo:data-changed', broadcast);
    document.addEventListener('submit', event => { if (event.target.method?.toLowerCase() !== 'get') broadcast(); }, true);
    window.addEventListener('pageshow', event => { if (event.persisted) { invalidate(); location.reload(); } });
    document.querySelectorAll('form[data-auto-filter]').forEach(form => {
        const region = form.hasAttribute('data-cache-filter') ? document.querySelector('[data-filter-results]') : null;
        let timer, controller, sequence = 0, submitting = false;
        const status = document.createElement('p');
        status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
        status.style.cssText = 'font-size:12px;margin-top:8px;';
        if (region) form.after(status);
        const formUrl = () => {
            const url = new URL(form.action);
            url.search = new URLSearchParams(new FormData(form)).toString(); return canonical(url.href);
        };
        const capture = doc => ({html: doc.querySelector('[data-filter-results]').innerHTML,
            exports: [...doc.querySelectorAll('[data-filter-export]')].map(link => link.getAttribute('href'))});
        const render = (data, url, push) => {
            region.innerHTML = data.html;
            document.querySelectorAll('[data-filter-export]').forEach((link, index) => { if (data.exports[index]) link.href = data.exports[index]; });
            if (push && location.href !== url) history.pushState({}, '', url);
        };
        if (region) { memory.put(canonical(location.href), capture(document)); memory.put(formUrl(), capture(document)); }
        async function load(url, push = true) {
            const current = ++sequence;
            controller?.abort(); controller = new AbortController();
            const cached = memory.get(url);
            if (cached) { render(cached, url, push); status.textContent = ''; region.removeAttribute('aria-busy'); return; }
            region.setAttribute('aria-busy', 'true'); status.textContent = 'Updating results…';
            try {
                const response = await fetch(url, {signal: controller.signal, credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
                if (response.status === 422) {
                    const problem = await response.json();
                    if (current === sequence) status.textContent = Object.values(problem.errors || {}).flat().join(' ') || 'Please check the filters.';
                    return;
                }
                if (!response.ok || response.redirected) { location.assign(url); return; }
                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                if (!doc.querySelector('[data-filter-results]')) { location.assign(url); return; }
                if (current !== sequence) return;
                const data = capture(doc); memory.put(url, data); render(data, url, push); status.textContent = '';
            } catch (error) {
                if (error.name !== 'AbortError' && current === sequence) status.textContent = 'Could not update results. Try Apply Filters again.';
            } finally { if (current === sequence) region.removeAttribute('aria-busy'); }
        }
        const submit = () => {
            clearTimeout(timer);
            for (const [minimum, maximum] of [['min_price', 'max_price'], ['min_stock', 'max_stock'], ['start_date', 'end_date']]) {
                const lower = form.elements.namedItem(minimum), upper = form.elements.namedItem(maximum);
                if (lower && upper) { if (lower.value) upper.min = lower.value; else upper.removeAttribute('min'); }
            }
            if (!submitting && form.reportValidity()) form.requestSubmit();
        };
        form.addEventListener('submit', event => { clearTimeout(timer); if (region) { event.preventDefault(); load(formUrl()); } else submitting = true; });
        form.addEventListener('change', () => { clearTimeout(timer); timer = setTimeout(submit, 350); });
        form.querySelectorAll('input[type="search"], input[name="search"]').forEach(input => {
            input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(submit, 500); });
        });
        if (region) {
            region.addEventListener('click', event => {
                const link = event.target.closest('a[href]');
                if (link && !event.ctrlKey && !event.metaKey && event.button === 0) {
                    const url = new URL(link.href);
                    if (url.origin === location.origin && url.pathname === location.pathname) { event.preventDefault(); load(canonical(url.href)); }
                }
            });
            window.addEventListener('popstate', () => {
                const url = new URL(location.href);
                for (const control of form.elements) {
                    if (!control.name || control.type === 'submit') continue;
                    const value = url.searchParams.get(control.name);
                    if (control.type === 'radio' || control.type === 'checkbox') control.checked = value === null ? control.defaultChecked : control.value === value;
                    else if (value !== null) control.value = value;
                    else if (control.tagName === 'SELECT') control.value = [...control.options].find(option => option.defaultSelected)?.value ?? control.options[0]?.value ?? '';
                    else control.value = control.defaultValue;
                }
                load(canonical(location.href), false);
            });
        }
    });
}
