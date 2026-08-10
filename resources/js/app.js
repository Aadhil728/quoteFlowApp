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
document.querySelectorAll('[data-searchable-select]').forEach((select) => {
    const trigger = select.querySelector('[data-select-trigger]');
    const popover = select.querySelector('[data-select-popover]');
    const search = select.querySelector('[data-select-search]');
    const value = select.querySelector('[data-select-value]');
    const options = [...select.querySelectorAll('[data-select-option]')];
    const empty = select.querySelector('[data-select-empty]');

    const visibleOptions = () => options.filter((option) => !option.hidden);
    const filter = () => {
        const query = search.value.trim().toLocaleLowerCase();
        options.forEach((option) => { option.hidden = !option.dataset.search.includes(query); });
        empty.hidden = visibleOptions().length !== 0;
    };
    const close = (returnFocus = false) => {
        popover.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        search.value = '';
        filter();
        if (returnFocus) trigger.focus();
    };
    const open = () => {
        document.querySelectorAll('[data-searchable-select]').forEach((other) => {
            if (other !== select) other.dispatchEvent(new CustomEvent('searchable-select:close'));
        });
        popover.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        search.focus();
    };
    const choose = (option) => {
        value.value = option.dataset.value;
        select.querySelector('[data-selected-code]').textContent = option.dataset.code;
        select.querySelector('[data-selected-label]').textContent = option.dataset.label;
        select.querySelector('[data-selected-meta]').textContent = option.dataset.meta;
        options.forEach((item) => item.setAttribute('aria-selected', String(item === option)));
        value.dispatchEvent(new Event('change', { bubbles: true }));
        close(true);
    };
    const moveFocus = (direction) => {
        const visible = visibleOptions();
        const current = visible.indexOf(document.activeElement);
        visible[(current + direction + visible.length) % visible.length]?.focus();
    };

    trigger.addEventListener('click', () => popover.hidden ? open() : close());
    trigger.addEventListener('keydown', (event) => {
        if (['ArrowDown', 'Enter', ' '].includes(event.key)) { event.preventDefault(); open(); }
    });
    search.addEventListener('input', filter);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') { event.preventDefault(); visibleOptions()[0]?.focus(); }
        if (event.key === 'Escape') { event.preventDefault(); close(true); }
        if (event.key === 'Enter' && visibleOptions().length === 1) { event.preventDefault(); choose(visibleOptions()[0]); }
    });
    options.forEach((option) => {
        option.addEventListener('click', () => choose(option));
        option.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') { event.preventDefault(); moveFocus(1); }
            if (event.key === 'ArrowUp') { event.preventDefault(); moveFocus(-1); }
            if (event.key === 'Escape') { event.preventDefault(); close(true); }
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); choose(option); }
        });
    });
    select.addEventListener('searchable-select:close', () => close());
    document.addEventListener('click', (event) => { if (!select.contains(event.target)) close(); });
});
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
