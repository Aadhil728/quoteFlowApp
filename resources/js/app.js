document.querySelectorAll('[data-nav-toggle]').forEach((button) => button.addEventListener('click', () => document.querySelector('[data-shell]')?.classList.toggle('nav-open')));
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
function syncSidebarToggle() {
    if (!sidebarToggle) return;
    const collapsed = document.documentElement.dataset.sidebar === 'collapsed';
    sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
    sidebarToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    sidebarToggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
}
sidebarToggle?.addEventListener('click', () => {
    const next = document.documentElement.dataset.sidebar === 'collapsed' ? 'expanded' : 'collapsed';
    document.documentElement.dataset.sidebar = next;
    localStorage.setItem('qf-sidebar', next);
    syncSidebarToggle();
});
syncSidebarToggle();
document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    localStorage.setItem('qf-theme', next);
});

const quotationForm = document.querySelector('[data-quotation-form]');
const itemsContainer = document.querySelector('[data-items]');
function reindexItems() {
    itemsContainer?.querySelectorAll('[data-item]').forEach((row, index) => row.querySelectorAll('[name],[data-name]').forEach((input) => {
        const field = input.dataset.name || input.name.match(/\[([^\]]+)\]$/)?.[1];
        if (field) input.name = `items[${index}][${field}]`;
    }));
}
document.querySelector('[data-add-item]')?.addEventListener('click', () => { const fragment = document.querySelector('#item-template').content.cloneNode(true); itemsContainer.append(fragment); reindexItems(); });
document.addEventListener('click', (event) => { if (event.target.closest('[data-remove-item]') && itemsContainer?.children.length > 1) { event.target.closest('[data-item]').remove(); reindexItems(); } });

if (quotationForm?.querySelector('input[name="_method"]')) {
    let autosaveTimer;
    quotationForm.addEventListener('input', () => {
        const status = document.querySelector('[data-save-status]');
        if (status) status.textContent = 'Unsaved changes';
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(async () => {
            if (status) status.textContent = 'Saving…';
            const response = await fetch(quotationForm.action, { method: 'POST', body: new FormData(quotationForm), headers: { Accept: 'application/json' } });
            if (status) status.textContent = response.ok ? 'Saved just now' : 'Save failed — use Save quotation';
        }, 1800);
    });
}
