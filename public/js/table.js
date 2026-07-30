var currentSortField = window.currentSortField || '';
var currentSortDir = window.currentSortDir || 'asc';
var mSelected = window.mSelected || new Set();

function initTable(sortField, sortDir) {
    currentSortField = sortField;
    currentSortDir = sortDir;
}

function buildUrl() {
    const params = new URLSearchParams();
    const q = document.getElementById('searchInput').value.trim();
    const field = document.getElementById('filterField').value;
    const perPage = document.getElementById('perPage').value;

    // Build URL from main search
    if (q) {
        if (field === 'price') {
            params.set('filters[price][$eq]', q);
        } else {
            params.set('filters[' + field + '][$contains]', q);
        }
        params.set('_field', field);
        params.set('_q', q);
    }

    // Build URL from advanced filters
    document.querySelectorAll('[data-field]').forEach(input => {
        const fieldName = input.dataset.field;
        const operator = document.querySelector('[data-op="' + fieldName + '"]')?.value || '$eq';
        const value = input.tagName === 'SELECT' ? input.value : input.value.trim();
        if (value) {
            params.set('filters[' + fieldName + '][' + operator + ']', value);
            params.set('filter_op[' + fieldName + ']', operator);
        }
    });

    // Also save filter operators even if value is empty
    document.querySelectorAll('[data-op]').forEach(select => {
        const fieldName = select.dataset.op;
        if (!params.has('filters[' + fieldName + ']')) {
            params.set('filter_op[' + fieldName + ']', select.value);
        }
    });

    if (currentSortField) params.set('sort[0]', currentSortField + ':' + currentSortDir);
    params.set('per_page', perPage);

    const module = document.querySelector('input.module').value;
    window.location.href = '/' + module + '/table?' + params.toString();
}

function doSort(col) {
    if (currentSortField === col) {
        currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortField = col;
        currentSortDir = 'asc';
    }
    buildUrl();
}

function updateFilterOp(fieldName) {
    // Operator is stored in select[data-op], this function can be used for future enhancements
}

function applyAdvanced() {
    document.getElementById('advFilter').classList.add('hidden');
    buildUrl();
}

function resetAdvanced() {
    document.querySelectorAll('[data-field]').forEach(input => input.value = '');
    document.querySelectorAll('[data-op]').forEach(select => select.value = '$eq');
    applyAdvanced();
}

function toggleAll(el) {
    document.querySelectorAll('tbody input[type="checkbox"]').forEach(c => c.checked = el.checked);
}

function mToggle(el) {
    const id = el.dataset.id;
    const icon = el.querySelector('[data-check]');
    if (mSelected.has(id)) {
        mSelected.delete(id);
        el.style.backgroundColor = '';
        icon.className = 'icon-[tabler--circle] size-5 text-base-content/20 shrink-0';
    } else {
        mSelected.add(id);
        el.style.backgroundColor = 'rgba(0,0,0,0.03)';
        icon.className = 'icon-[tabler--circle-check-filled] size-5 text-primary shrink-0';
    }
    updateMSel();
}

function mToggleAll() {
    const items = document.querySelectorAll('#mBody > div[data-id]');
    if (mSelected.size) {
        mSelected.clear();
        items.forEach(el => { el.style.backgroundColor=''; el.querySelector('[data-check]').className='icon-[tabler--circle] size-5 text-base-content/20 shrink-0'; });
    } else {
        items.forEach(el => { mSelected.add(el.dataset.id); el.style.backgroundColor='rgba(0,0,0,0.03)'; el.querySelector('[data-check]').className='icon-[tabler--circle-check-filled] size-5 text-primary shrink-0'; });
    }
    updateMSel();
}

function updateMSel() {
    document.getElementById('mSelCount').textContent = mSelected.size ? mSelected.size + ' selected' : '';
    document.getElementById('mToggleAll').textContent = mSelected.size ? 'Unselect' : 'Select All';
}

function deleteSelected() {
    const desktopIds = [...document.querySelectorAll('tbody input[type="checkbox"]:checked')].map(c => c.value);
    const ids = desktopIds.length ? desktopIds : [...mSelected];
    if (!ids.length) return alert('No items selected');
    if (!confirm(`Delete ${ids.length} product(s)?`)) return;
    const form = document.createElement('form');
    const module = document.querySelector('input.module').value;
    form.method = 'POST'; form.action = '/' + module + '/delete-bulk';
    form.innerHTML = document.querySelector('meta[name="csrf-token"]').content ? `<input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]').content}">` : '';
    ids.forEach(id => { form.innerHTML += `<input type="hidden" name="ids[]" value="${id}">`; });
    document.body.appendChild(form); form.submit();
}