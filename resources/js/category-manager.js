function initializeCategoryManager() {
    const modal = document.getElementById('categoryManager');
    if (!modal) return;
    const byId = id => document.getElementById(id);
    const form = byId('categoryManagerForm');
    const name = byId('categoryManagerName');
    const list = byId('categoryManagerList');
    const error = byId('categoryManagerError');
    const status = byId('categoryManagerStatus');
    const confirmation = byId('categoryArchiveConfirmation');
    let categories = [], tab = 'active', editingId = null, archiveId = null;
    let busy = false, quickCreate = false, returnFocus = null, previousOverflow = '';

    function message(element, text = '') {
        element.textContent = text;
        element.classList.toggle('hidden', !text);
    }
    function setBusy(value) {
        busy = value;
        modal.querySelectorAll('button, input').forEach(control => { control.disabled = value; });
        list.setAttribute('aria-busy', String(value));
    }
    function render() {
        const active = categories.filter(category => !category.is_archived).length;
        byId('categoryActiveCount').textContent = active;
        byId('categoryArchivedCount').textContent = categories.length - active;
        for (const [id, value] of [['categoryActiveTab', 'active'], ['categoryArchivedTab', 'archived']]) {
            const button = byId(id), selected = tab === value;
            button.setAttribute('aria-pressed', String(selected));
            button.className = `px-4 py-2 rounded-xl font-bold text-sm ${selected ? 'bg-red-600 text-white' : 'bg-slate-100 dark:bg-dark-800'}`;
        }
        const search = byId('categoryManagerSearch').value.trim().toLocaleLowerCase();
        const shown = categories.filter(category => category.is_archived === (tab === 'archived') && category.name.toLocaleLowerCase().includes(search));
        list.replaceChildren();
        for (const category of shown) {
            const row = document.createElement('div');
            row.setAttribute('role', 'listitem');
            row.className = 'flex flex-wrap items-center justify-between gap-3 p-4 rounded-2xl border border-slate-200 dark:border-slate-700';
            const details = document.createElement('div');
            details.className = 'min-w-0 flex-1';
            const title = document.createElement('p');
            title.className = 'font-bold break-words';
            title.textContent = category.name;
            const count = document.createElement('a');
            count.className = 'text-xs text-blue-600 dark:text-blue-400 underline underline-offset-2';
            count.href = category.products_url;
            count.textContent = `${category.products_count} ${category.products_count === 1 ? 'product' : 'products'}`;
            details.append(title, count);
            const actions = document.createElement('div');
            actions.className = 'flex items-center gap-2';
            for (const [label, action, style] of [
                ['Edit', () => showForm(category), 'bg-slate-100 dark:bg-dark-800'],
                [category.is_archived ? 'Restore' : 'Archive', () => category.is_archived ? restore(category) : askArchive(category), category.is_archived ? 'bg-emerald-600 text-white' : 'bg-amber-500/15 text-amber-800 dark:text-amber-300'],
            ]) {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = label;
                button.className = `px-3 py-2 rounded-xl font-bold text-xs ${style}`;
                button.disabled = busy;
                button.addEventListener('click', action);
                actions.append(button);
            }
            row.append(details, actions);
            list.append(row);
        }
        if (!shown.length) {
            const empty = document.createElement('p');
            empty.className = 'py-8 text-center text-sm text-slate-500 dark:text-slate-400';
            empty.textContent = search ? 'No categories match your search.' : `No ${tab} categories.`;
            list.append(empty);
        }
    }
    function synchronize(category, created = false) {
        const existing = categories.findIndex(item => item.id === category.id);
        if (existing < 0) categories.push(category); else categories[existing] = category;
        categories.sort((a, b) => a.name.localeCompare(b.name));
        document.querySelectorAll('[data-category-select]').forEach(select => {
            const selected = select.value;
            const placeholder = select.querySelector('option[value=""]')?.textContent || 'Select Category';
            const allowed = categories.filter(item => select.dataset.categorySelect === 'filter' || !item.is_archived || String(item.id) === select.dataset.currentCategory);
            select.replaceChildren(new Option(placeholder, ''), ...allowed.map(item => new Option(item.name + (item.is_archived ? ' (Archived)' : ''), item.id)));
            select.value = allowed.some(item => String(item.id) === selected) ? selected : '';
            if (quickCreate && created && select.dataset.categorySelect === 'product') select.value = String(category.id);
        });
        document.querySelectorAll('[data-category-label]').forEach(label => {
            if (label.dataset.categoryLabel === String(category.id)) label.textContent = category.name;
        });
        document.querySelectorAll('[data-category-counter]').forEach(counter => {
            counter.textContent = categories.filter(item => item.is_archived === (counter.dataset.categoryCounter === 'archived')).length;
        });
        render();
    }
    async function request(url, method = 'GET', data) {
        const response = await fetch(url, {method, credentials: 'same-origin', headers: {
            Accept: 'application/json', 'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }, ...(data ? {body: JSON.stringify(data)} : {})});
        const result = await response.json();
        if (!response.ok) throw new Error(result.errors?.Name?.[0] || result.message || 'Unable to save category.');
        return result;
    }
    async function load() {
        if (busy) return;
        setBusy(true);
        message(error);
        list.textContent = 'Loading categories...';
        try { categories = (await request(modal.dataset.indexUrl)).categories; render(); }
        catch (failure) { list.textContent = ''; message(error, failure.message || 'Unable to load categories. Please refresh.'); }
        finally { setBusy(false); }
    }
    function showForm(category = null) {
        confirmation.classList.add('hidden');
        archiveId = null;
        editingId = category?.id ?? null;
        byId('categoryManagerFormHeading').textContent = category ? 'Edit Category' : 'Add Category';
        name.value = category?.name ?? '';
        form.classList.remove('hidden');
        message(error);
        message(status);
        name.focus();
    }
    function close() {
        if (busy) return;
        modal.classList.replace('flex', 'hidden');
        document.body.style.overflow = previousOverflow;
        returnFocus?.focus();
    }
    window.openCategoryManager = async (mode = 'manage') => {
        if (!modal.classList.contains('hidden')) return;
        returnFocus = document.activeElement;
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        quickCreate = mode === 'create';
        tab = 'active';
        form.classList.add('hidden');
        confirmation.classList.add('hidden');
        byId('categoryManagerSearch').value = '';
        message(status);
        modal.classList.replace('hidden', 'flex');
        await load();
        if (quickCreate) showForm(); else byId('categoryManagerSearch').focus();
    };
    window.openCategoryCreator = () => window.openCategoryManager('create');
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || !form.reportValidity()) return;
        const created = editingId === null;
        const url = created ? modal.dataset.storeUrl : categories.find(item => item.id === editingId).update_url;
        setBusy(true);
        message(error);
        try {
            const result = await request(url, created ? 'POST' : 'PUT', {Name: name.value.trim()});
            synchronize(result.category, created);
            form.classList.add('hidden');
            message(status, result.message);
            setBusy(false);
            if (quickCreate && created) close(); else byId('categoryManagerAdd').focus();
        } catch (failure) { message(error, failure.message); }
        finally { setBusy(false); }
    });
    function askArchive(category) {
        form.classList.add('hidden');
        archiveId = category.id;
        byId('categoryArchiveQuestion').textContent = `Archive “${category.name}”?`;
        confirmation.classList.remove('hidden');
        message(error);
        message(status);
        byId('categoryArchiveCancel').focus();
    }
    async function changeArchive(category, restoring) {
        if (busy) return;
        setBusy(true);
        message(error);
        try {
            const result = await request(restoring ? category.restore_url : category.archive_url, 'POST');
            synchronize(result.category);
            confirmation.classList.add('hidden');
            archiveId = null;
            message(status, result.message);
        } catch (failure) { message(error, failure.message); }
        finally { setBusy(false); byId(restoring ? 'categoryArchivedTab' : 'categoryActiveTab').focus(); }
    }
    const restore = category => changeArchive(category, true);
    byId('categoryArchiveConfirm').addEventListener('click', () => changeArchive(categories.find(item => item.id === archiveId), false));
    byId('categoryArchiveCancel').addEventListener('click', () => { confirmation.classList.add('hidden'); archiveId = null; byId('categoryActiveTab').focus(); });
    byId('categoryManagerCancelEdit').addEventListener('click', () => { form.classList.add('hidden'); byId('categoryManagerAdd').focus(); });
    byId('categoryManagerAdd').addEventListener('click', () => showForm());
    byId('categoryManagerRefresh').addEventListener('click', load);
    byId('categoryManagerClose').addEventListener('click', close);
    byId('categoryManagerSearch').addEventListener('input', render);
    for (const [id, value] of [['categoryActiveTab', 'active'], ['categoryArchivedTab', 'archived']]) {
        byId(id).addEventListener('click', () => { tab = value; form.classList.add('hidden'); confirmation.classList.add('hidden'); render(); });
    }
    modal.addEventListener('click', event => { if (event.target === modal) close(); });
    modal.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); close(); }
        if (event.key !== 'Tab') return;
        const controls = [...modal.querySelectorAll('button, input, a[href]')].filter(control => !control.disabled && control.getClientRects().length);
        const first = controls[0], last = controls.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeCategoryManager);
else initializeCategoryManager();
