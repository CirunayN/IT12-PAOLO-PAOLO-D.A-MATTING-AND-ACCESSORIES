import Alpine from 'alpinejs';
import './category-manager';

window.Alpine = Alpine;

Alpine.start();

function initializeAutomaticFilters() {
    document.querySelectorAll('form[data-auto-filter]').forEach(form => {
        let timer;
        let submitting = false;
        const submit = () => {
            clearTimeout(timer);
            if (submitting || !form.reportValidity()) return;
            submitting = true;
            form.requestSubmit();
        };
        form.addEventListener('change', submit);
        form.querySelectorAll('input[type="search"], input[name="search"]').forEach(input => {
            input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(submit, 500); });
        });
        window.addEventListener('pageshow', () => { submitting = false; clearTimeout(timer); form.reset(); });
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeAutomaticFilters);
else initializeAutomaticFilters();
