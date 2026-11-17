/**
 * PION Global Searchable Select (Select2-like)
 *
 * Meningkatkan seluruh <select> pada form Create/Edit, Filter, dan Modal agar memiliki
 * kotak pencarian cepat serta batas tinggi scroll yang ringkas tanpa merusak script cascading
 * existing (option.hidden, option.disabled, select.value = ..., onchange, HTML5 required).
 */

const INSTANCE_MAP = new WeakMap();
const EXCLUDED_CONTAINER_SELECTOR = '.dataTables_length, .dataTables_filter, .dataTables_paginate, .swal2-container, .swal2-popup, .select2-container, .daterangepicker, .ui-datepicker';
const CHEVRON_SVG = '<svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5.25 7.5L10 12.25L14.75 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

const nativeValueDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
const nativeSelectedIndexDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');

let activeState = null;
let sharedDropdown = null;
let globalListenersAttached = false;
let domObserver = null;

function shouldEnhanceSelect(selectEl) {
    if (!(selectEl instanceof HTMLSelectElement)) {
        return false;
    }

    if (INSTANCE_MAP.has(selectEl) || selectEl.dataset.s2Init === '1') {
        return false;
    }

    if (selectEl.multiple || selectEl.classList.contains('no-select2') || selectEl.classList.contains('swal2-select') || selectEl.classList.contains('select2-hidden-accessible')) {
        return false;
    }

    if (selectEl.dataset.noSelect2 === 'true' || selectEl.dataset.searchable === 'false') {
        return false;
    }

    if (selectEl.hidden || selectEl.style.display === 'none' || selectEl.classList.contains('d-none') || selectEl.classList.contains('hidden')) {
        return false;
    }

    if (typeof window !== 'undefined' && window.getComputedStyle(selectEl).display === 'none') {
        return false;
    }

    if (selectEl.closest(EXCLUDED_CONTAINER_SELECTOR) || /(?:^|_)length$/i.test(selectEl.name || '')) {
        return false;
    }

    // Hindari mengubah select kecil di dalam sel tabel (mis. Tuntas/Belum Tuntas) kecuali diizinkan eksplisit
    if (selectEl.closest('table') && selectEl.dataset.select2 !== 'true') {
        return false;
    }

    return true;
}

function getSelectedOption(selectEl) {
    const idx = selectEl.selectedIndex;
    if (idx >= 0 && idx < selectEl.options.length) {
        return selectEl.options[idx];
    }

    return null;
}

function hasEmptyClearOption(selectEl) {
    return Array.from(selectEl.options).some((opt) => opt.value === '' && !opt.hidden && !opt.disabled);
}

function safeFocus(el) {
    if (!el || document.activeElement === el) {
        return;
    }

    try {
        el.focus({ preventScroll: true });
    } catch {
        el.focus();
    }
}

function syncWrapperState(state) {
    const { selectEl, wrapper, trigger, valueEl, clearEl } = state;

    wrapper.classList.toggle('is-invalid', selectEl.classList.contains('is-invalid'));
    wrapper.classList.toggle('is-disabled', selectEl.disabled);
    trigger.disabled = selectEl.disabled;

    const selectedOpt = getSelectedOption(selectEl);
    const displayText = selectedOpt ? selectedOpt.textContent.trim() : '-- Pilih --';
    const isPlaceholder = !selectedOpt || selectedOpt.value === '';

    valueEl.textContent = displayText;
    valueEl.classList.toggle('is-placeholder', isPlaceholder);
    trigger.title = displayText;

    const canClear = !selectEl.required && !selectEl.disabled && !isPlaceholder && hasEmptyClearOption(selectEl);
    clearEl.classList.toggle('is-hidden', !canClear);
}

function hookSelectProperties(state) {
    const { selectEl } = state;
    if (selectEl.dataset.s2Hooked === '1') {
        return;
    }
    selectEl.dataset.s2Hooked = '1';

    if (nativeValueDesc && nativeValueDesc.get && nativeValueDesc.set) {
        Object.defineProperty(selectEl, 'value', {
            configurable: true,
            get() {
                return nativeValueDesc.get.call(this);
            },
            set(newVal) {
                nativeValueDesc.set.call(this, newVal);
                syncWrapperState(state);
                if (activeState === state) {
                    renderDropdownOptions(state);
                }
            },
        });
    }

    if (nativeSelectedIndexDesc && nativeSelectedIndexDesc.get && nativeSelectedIndexDesc.set) {
        Object.defineProperty(selectEl, 'selectedIndex', {
            configurable: true,
            get() {
                return nativeSelectedIndexDesc.get.call(this);
            },
            set(newIdx) {
                nativeSelectedIndexDesc.set.call(this, newIdx);
                syncWrapperState(state);
                if (activeState === state) {
                    renderDropdownOptions(state);
                }
            },
        });
    }
}

function ensureSharedDropdown() {
    if (sharedDropdown) {
        return sharedDropdown;
    }

    const panel = document.createElement('div');
    panel.className = 's2-dropdown';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Filter dan pilih opsi');
    panel.innerHTML = `
        <div class="s2-search-box">
            <i class="fa fa-search s2-search-icon" aria-hidden="true"></i>
            <input type="search" class="s2-search-input" placeholder="Ketik untuk mencari..." autocomplete="off" spellcheck="false">
        </div>
        <ul class="s2-results" role="listbox"></ul>
    `;

    const searchInput = panel.querySelector('.s2-search-input');
    const resultsList = panel.querySelector('.s2-results');

    searchInput.addEventListener('input', () => {
        if (activeState) {
            activeState.highlightIdx = 0;
            renderDropdownOptions(activeState, searchInput.value);
        }
    });

    searchInput.addEventListener('keydown', (e) => {
        if (!activeState) {
            return;
        }

        const visibleItems = Array.from(resultsList.querySelectorAll('.s2-option:not(.is-disabled)'));
        if (visibleItems.length === 0) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closeDropdown(true);
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeState.highlightIdx = Math.min(visibleItems.length - 1, activeState.highlightIdx + 1);
            updateHighlight(visibleItems, activeState.highlightIdx);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeState.highlightIdx = Math.max(0, activeState.highlightIdx - 1);
            updateHighlight(visibleItems, activeState.highlightIdx);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const chosen = visibleItems[activeState.highlightIdx] || visibleItems[0];
            if (chosen) {
                selectOptionFromElement(activeState, chosen.dataset.value ?? '');
            }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            closeDropdown(true);
        } else if (e.key === 'Tab') {
            closeDropdown(false);
        }
    });

    resultsList.addEventListener('click', (e) => {
        if (!activeState) {
            return;
        }
        const optEl = e.target.closest('.s2-option');
        if (!optEl || optEl.classList.contains('is-disabled')) {
            return;
        }

        e.preventDefault();
        selectOptionFromElement(activeState, optEl.dataset.value ?? '');
    });

    sharedDropdown = { panel, searchInput, resultsList };
    return sharedDropdown;
}

function updateHighlight(items, targetIdx) {
    items.forEach((el, idx) => {
        const isHighlighted = idx === targetIdx;
        el.classList.toggle('is-highlighted', isHighlighted);
        if (isHighlighted) {
            el.scrollIntoView({ block: 'nearest' });
        }
    });
}

function matchesSearchTokens(label, queryTokens) {
    if (queryTokens.length === 0) {
        return true;
    }
    const lower = label.toLowerCase();
    return queryTokens.every((token) => lower.includes(token));
}

function buildOptionItems(selectEl, queryText) {
    const queryTokens = (queryText || '').trim().toLowerCase().split(/\s+/).filter(Boolean);
    const items = [];

    const appendOption = (opt) => {
        if (opt.hidden || opt.style.display === 'none') {
            return;
        }

        const label = opt.textContent.trim();
        if (!matchesSearchTokens(label, queryTokens)) {
            return;
        }

        items.push({
            type: 'option',
            value: opt.value,
            label,
            disabled: opt.disabled,
            selected: opt.selected,
        });
    };

    Array.from(selectEl.children).forEach((child) => {
        if (child.tagName === 'OPTGROUP') {
            if (child.hidden || child.disabled) {
                return;
            }
            const groupStartIndex = items.length;
            Array.from(child.children).forEach((subChild) => {
                if (subChild.tagName === 'OPTION') {
                    appendOption(subChild);
                }
            });
            if (items.length > groupStartIndex) {
                items.splice(groupStartIndex, 0, {
                    type: 'group',
                    label: child.label || '',
                });
            }
        } else if (child.tagName === 'OPTION') {
            appendOption(child);
        }
    });

    return items;
}

function renderDropdownOptions(state, queryText = null) {
    const { resultsList, searchInput } = ensureSharedDropdown();
    const query = queryText === null ? searchInput.value : queryText;
    const entries = buildOptionItems(state.selectEl, query);

    resultsList.innerHTML = '';

    if (entries.length === 0) {
        const emptyLi = document.createElement('li');
        emptyLi.className = 's2-empty';
        emptyLi.textContent = 'Tidak ada pilihan yang cocok';
        resultsList.appendChild(emptyLi);
        return;
    }

    const selectableEls = [];
    let selectedRenderIdx = -1;

    entries.forEach((entry) => {
        if (entry.type === 'group') {
            const groupLi = document.createElement('li');
            groupLi.className = 's2-group-label';
            groupLi.textContent = entry.label;
            resultsList.appendChild(groupLi);
            return;
        }

        const li = document.createElement('li');
        li.className = 's2-option';
        li.setAttribute('role', 'option');
        li.dataset.value = entry.value;

        if (entry.value === '') {
            li.classList.add('is-placeholder-option');
        }
        if (entry.disabled) {
            li.classList.add('is-disabled');
        }
        if (entry.selected) {
            li.classList.add('is-selected');
            li.setAttribute('aria-selected', 'true');
            selectedRenderIdx = selectableEls.length;
        } else {
            li.setAttribute('aria-selected', 'false');
        }

        const textSpan = document.createElement('span');
        textSpan.className = 's2-option-text';
        textSpan.textContent = entry.label;
        li.appendChild(textSpan);

        if (entry.selected) {
            const checkSpan = document.createElement('span');
            checkSpan.className = 's2-option-check';
            checkSpan.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i>';
            li.appendChild(checkSpan);
        }

        resultsList.appendChild(li);

        if (!entry.disabled) {
            selectableEls.push(li);
        }
    });

    if (selectableEls.length > 0) {
        if (query.trim() === '' && selectedRenderIdx >= 0) {
            state.highlightIdx = selectedRenderIdx;
        } else {
            state.highlightIdx = Math.max(0, Math.min(state.highlightIdx, selectableEls.length - 1));
        }
        updateHighlight(selectableEls, state.highlightIdx);
    }
}

function positionDropdown(state) {
    const { panel } = ensureSharedDropdown();
    const rect = state.trigger.getBoundingClientRect();
    const margin = 8;
    const gap = 4;

    const minWidth = Math.max(rect.width, 220);
    const maxAllowedWidth = Math.max(180, window.innerWidth - margin * 2);
    const dropdownWidth = Math.min(minWidth, maxAllowedWidth);

    let left = rect.left;
    if (left + dropdownWidth > window.innerWidth - margin) {
        left = Math.max(margin, window.innerWidth - dropdownWidth - margin);
    }

    panel.style.width = `${dropdownWidth}px`;
    panel.style.left = `${left}px`;
    panel.style.visibility = 'hidden';
    panel.classList.add('is-open');

    const panelHeight = panel.offsetHeight || 280;
    const spaceBelow = window.innerHeight - rect.bottom - margin;
    const spaceAbove = rect.top - margin;

    let top = rect.bottom + gap;
    if (spaceBelow < Math.min(panelHeight, 260) && spaceAbove > spaceBelow) {
        top = Math.max(margin, rect.top - panelHeight - gap);
    } else if (top + panelHeight > window.innerHeight - margin) {
        top = Math.max(margin, window.innerHeight - panelHeight - margin);
    }

    panel.style.top = `${top}px`;
    panel.style.visibility = '';
}

function selectOptionFromElement(state, newValue) {
    const { selectEl } = state;
    const previousValue = selectEl.value;

    closeDropdown(true);

    if (previousValue !== newValue) {
        if (nativeValueDesc && nativeValueDesc.set) {
            nativeValueDesc.set.call(selectEl, newValue);
        } else {
            selectEl.value = newValue;
        }
        syncWrapperState(state);
        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function openDropdown(state) {
    if (state.selectEl.disabled) {
        return;
    }

    if (activeState === state) {
        closeDropdown(true);
        return;
    }

    if (activeState) {
        closeDropdown(false);
    }

    const { panel, searchInput } = ensureSharedDropdown();
    const portalHost = state.selectEl.closest('.modal') || document.body;

    if (panel.parentElement !== portalHost) {
        portalHost.appendChild(panel);
    }

    activeState = state;
    state.highlightIdx = 0;
    state.trigger.setAttribute('aria-expanded', 'true');
    state.wrapper.classList.add('is-open');

    searchInput.value = '';
    renderDropdownOptions(state, '');
    positionDropdown(state);

    requestAnimationFrame(() => {
        if (activeState === state) {
            safeFocus(searchInput);
        }
    });
}

function closeDropdown(restoreTriggerFocus = false) {
    if (!activeState) {
        return;
    }

    const prev = activeState;
    activeState = null;

    prev.trigger.setAttribute('aria-expanded', 'false');
    prev.wrapper.classList.remove('is-open');

    if (sharedDropdown) {
        sharedDropdown.panel.classList.remove('is-open');
    }

    if (restoreTriggerFocus) {
        safeFocus(prev.trigger);
    }
}

function enhanceSelect(selectEl) {
    if (!shouldEnhanceSelect(selectEl)) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 's2-wrapper';
    if (selectEl.classList.contains('form-control-sm')) {
        wrapper.classList.add('is-sm');
    }
    if (selectEl.classList.contains('filter-control')) {
        wrapper.classList.add('is-filter');
    }
    if (selectEl.classList.contains('tp-control')) {
        wrapper.classList.add('is-tp');
    }
    if (selectEl.classList.contains('ak-filter-select')) {
        wrapper.classList.add('is-ak');
    }

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 's2-trigger';
    trigger.setAttribute('role', 'combobox');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const valueEl = document.createElement('span');
    valueEl.className = 's2-value';

    const actionsEl = document.createElement('span');
    actionsEl.className = 's2-actions';

    const clearEl = document.createElement('span');
    clearEl.className = 's2-clear is-hidden';
    clearEl.setAttribute('title', 'Bersihkan pilihan');
    clearEl.setAttribute('role', 'button');
    clearEl.setAttribute('aria-label', 'Bersihkan pilihan');
    clearEl.textContent = '×';

    const arrowEl = document.createElement('span');
    arrowEl.className = 's2-arrow';
    arrowEl.innerHTML = CHEVRON_SVG;

    actionsEl.appendChild(clearEl);
    actionsEl.appendChild(arrowEl);
    trigger.appendChild(valueEl);
    trigger.appendChild(actionsEl);

    const parent = selectEl.parentNode;
    if (!parent) {
        return;
    }

    parent.insertBefore(wrapper, selectEl);
    wrapper.appendChild(selectEl);
    wrapper.appendChild(trigger);

    selectEl.dataset.s2Init = '1';
    selectEl.classList.add('s2-native-select');
    selectEl.tabIndex = -1;

    const state = {
        selectEl,
        wrapper,
        trigger,
        valueEl,
        clearEl,
        highlightIdx: 0,
    };

    INSTANCE_MAP.set(selectEl, state);
    hookSelectProperties(state);
    syncWrapperState(state);

    trigger.addEventListener('click', (e) => {
        if (e.target.closest('.s2-clear')) {
            e.preventDefault();
            e.stopPropagation();
            selectOptionFromElement(state, '');
            return;
        }
        e.preventDefault();
        openDropdown(state);
    });

    trigger.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openDropdown(state);
        }
    });

    selectEl.addEventListener('change', () => syncWrapperState(state));
    selectEl.addEventListener('invalid', () => {
        wrapper.classList.add('is-invalid');
        safeFocus(trigger);
    });
    selectEl.addEventListener('focus', () => {
        if (!activeState) {
            safeFocus(trigger);
        }
    });

    if (selectEl.form) {
        selectEl.form.addEventListener('reset', () => {
            requestAnimationFrame(() => syncWrapperState(state));
        });
    }

    // Amati mutasi <option hidden>, <option disabled>, penambahan opsi, atau class is-invalid
    const selectObserver = new MutationObserver(() => {
        syncWrapperState(state);
        if (activeState === state) {
            renderDropdownOptions(state);
            positionDropdown(state);
        }
    });
    selectObserver.observe(selectEl, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['hidden', 'disabled', 'selected', 'label', 'class', 'style'],
    });
}

function attachGlobalListeners() {
    if (globalListenersAttached) {
        return;
    }
    globalListenersAttached = true;

    document.addEventListener('click', (e) => {
        if (!activeState || !sharedDropdown) {
            return;
        }
        if (sharedDropdown.panel.contains(e.target) || activeState.wrapper.contains(e.target)) {
            return;
        }
        closeDropdown(false);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && activeState) {
            closeDropdown(true);
        }
    });

    window.addEventListener('resize', () => {
        if (activeState) {
            positionDropdown(activeState);
        }
    });

    window.addEventListener(
        'scroll',
        (e) => {
            if (!activeState || !sharedDropdown) {
                return;
            }
            if (sharedDropdown.panel.contains(e.target)) {
                return;
            }
            const rect = activeState.trigger.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > window.innerHeight) {
                closeDropdown(false);
                return;
            }
            positionDropdown(activeState);
        },
        { capture: true, passive: true }
    );
}

export function initSearchableSelects(root = document) {
    attachGlobalListeners();

    if (root instanceof HTMLSelectElement) {
        enhanceSelect(root);
        return;
    }

    if (!root || typeof root.querySelectorAll !== 'function') {
        return;
    }

    root.querySelectorAll('select').forEach((selectEl) => enhanceSelect(selectEl));

    if (!domObserver && document.body) {
        domObserver = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (!(node instanceof Element)) {
                        continue;
                    }
                    if (node.classList.contains('s2-dropdown') || node.classList.contains('s2-wrapper')) {
                        continue;
                    }
                    if (node instanceof HTMLSelectElement) {
                        enhanceSelect(node);
                    } else if (node.querySelector) {
                        node.querySelectorAll('select').forEach((sel) => enhanceSelect(sel));
                    }
                }
            }
        });
        domObserver.observe(document.body, { childList: true, subtree: true });
    }
}

export function refreshSearchableSelect(selectEl) {
    const state = INSTANCE_MAP.get(selectEl);
    if (!state) {
        return;
    }
    syncWrapperState(state);
    if (activeState === state) {
        renderDropdownOptions(state);
    }
}
