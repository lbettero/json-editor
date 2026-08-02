(() => {
    const state = { original: null, data: null, fileName: 'data.json', collections: [], collectionKey: null, records: [], schema: null, editingIndex: null };
    const $ = (selector) => document.querySelector(selector);
    const fileInput = $('#jsonFile');
    const notice = $('#notice');
    const workspace = $('#workspace');
    const structureTree = $('#structureTree');
    const recordsList = $('#recordsList');
    const dialog = $('#recordDialog');
    const form = $('#recordForm');
    const fields = $('#dynamicFields');

    const typeOf = (value) => value === null ? 'null' : Array.isArray(value) ? 'array' : typeof value;
    const clone = (value) => JSON.parse(JSON.stringify(value));
    const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));

    function mergeSchemas(a, b) {
        if (!a) return b;
        if (!b) return a;
        if (a.type !== b.type) return { type: 'mixed', types: [...new Set([...(a.types || [a.type]), ...(b.types || [b.type])])] };
        if (a.type === 'object') {
            const keys = new Set([...Object.keys(a.properties), ...Object.keys(b.properties)]);
            const properties = {};
            keys.forEach(key => {
                properties[key] = mergeSchemas(a.properties[key], b.properties[key]);
                properties[key].optional = !(key in a.properties) || !(key in b.properties) || a.properties[key]?.optional || b.properties[key]?.optional;
            });
            return { type: 'object', properties };
        }
        if (a.type === 'array') return { type: 'array', count: (a.count || 0) + (b.count || 0), items: mergeSchemas(a.items, b.items) };
        return a;
    }

    function inferSchema(value) {
        const type = typeOf(value);
        if (type === 'object') {
            const properties = {};
            Object.entries(value).forEach(([key, child]) => properties[key] = inferSchema(child));
            return { type, properties };
        }
        if (type === 'array') {
            return { type, count: value.length, items: value.reduce((schema, item) => mergeSchemas(schema, inferSchema(item)), null) || { type: 'unknown' } };
        }
        return { type };
    }

    function findCollections(data) {
        if (Array.isArray(data)) return [{ key: null, label: 'Root array', records: data }];
        if (data && typeof data === 'object') {
            const arrays = Object.entries(data).filter(([, value]) => Array.isArray(value));
            if (arrays.length) return arrays.map(([key, records]) => ({ key, label: key, records }));
            return [{ key: '__root_object__', label: 'Root object', records: [data] }];
        }
        return [];
    }

    function selectCollection(key) {
        const selected = state.collections.find(item => String(item.key) === String(key)) || state.collections[0];
        state.collectionKey = selected?.key ?? null;
        state.records = selected ? selected.records : [];
        state.schema = inferSchema(state.records);
        renderAll();
    }

    function loadData(data, name) {
        if (!data || (typeof data !== 'object')) throw new Error('The JSON root must be an object or an array.');
        state.original = clone(data);
        state.data = data;
        state.fileName = name || 'data.json';
        state.collections = findCollections(data);
        if (!state.collections.length) throw new Error('No editable collection was found.');
        workspace.hidden = false;
        notice.textContent = 'File loaded successfully. Select a view to explore it.';
        notice.className = 'notice notice-success';
        $('#fileName').textContent = state.fileName;
        setupCollectionSelect();
        selectCollection(state.collections[0].key);
    }

    function setupCollectionSelect() {
        const select = $('#collectionSelect');
        select.innerHTML = state.collections.map(item => `<option value="${escapeHtml(String(item.key))}">${escapeHtml(item.label)}</option>`).join('');
        $('#collectionLabel').hidden = state.collections.length < 2;
    }

    function schemaNode(name, schema, path = '$') {
        const hasChildren = schema?.type === 'object' || schema?.type === 'array';
        const label = name === null ? path : name;
        const meta = schema?.type === 'array' ? `${schema.type} · ${schema.count || 0} items` : schema?.type === 'mixed' ? `mixed: ${(schema.types || []).join(', ')}` : schema?.type || 'unknown';
        let children = '';
        if (schema?.type === 'object') {
            children = Object.entries(schema.properties).map(([key, child]) => schemaNode(key, child, `${path}.${key}`)).join('');
        } else if (schema?.type === 'array' && schema.items) {
            children = schemaNode('item', schema.items, `${path}[]`);
        }
        return `<details class="schema-node" ${path === '$' ? 'open' : ''}>
            <summary${hasChildren ? '' : ' class="schema-leaf"'}><code>${escapeHtml(label)}</code><span class="type-badge type-${escapeHtml(schema?.type || 'unknown')}">${escapeHtml(meta)}</span>${schema?.optional ? '<span class="optional-badge">optional</span>' : ''}</summary>
            ${children ? `<div class="schema-children">${children}</div>` : ''}
        </details>`;
    }

    function renderStructure() {
        structureTree.innerHTML = schemaNode(null, state.schema);
    }

    function summaryFor(record, index) {
        if (record && typeof record === 'object' && !Array.isArray(record)) {
            const preferred = ['name', 'title', 'label', 'id'];
            const key = preferred.find(candidate => record[candidate] !== undefined) || Object.keys(record).find(candidate => ['string', 'number'].includes(typeof record[candidate]));
            return key ? String(record[key]) : `Item ${index + 1}`;
        }
        return String(record);
    }

    function renderRecords() {
        const query = $('#recordSearch').value.trim().toLowerCase();
        const entries = state.records.map((record, index) => ({ record, index })).filter(({ record }) => !query || JSON.stringify(record).toLowerCase().includes(query));
        $('#recordCount').textContent = `${state.records.length} item${state.records.length === 1 ? '' : 's'}`;
        if (!entries.length) {
            recordsList.innerHTML = `<div class="empty-state"><div class="empty-icon">[ ]</div><h3>${state.records.length ? 'No matching items' : 'No items yet'}</h3><p>${state.records.length ? 'Try another search.' : 'Create the first item to start this collection.'}</p></div>`;
            return;
        }
        recordsList.innerHTML = entries.map(({ record, index }) => `<article class="record-card">
            <div><span class="record-index">#${index + 1}</span><h3>${escapeHtml(summaryFor(record, index))}</h3><pre>${escapeHtml(JSON.stringify(record, null, 2))}</pre></div>
            <div class="record-actions"><button type="button" data-edit="${index}">Edit</button><button class="danger" type="button" data-delete="${index}">Delete</button></div>
        </article>`).join('');
    }

    function renderAll() { state.schema = inferSchema(state.records); renderStructure(); renderRecords(); }

    function fieldHtml(key, schema, value, path) {
        const inputName = path ? `${path}.${key}` : key;
        const label = `<label for="field-${escapeHtml(inputName)}">${escapeHtml(key)}${schema.optional ? ' (optional)' : ''}</label>`;
        if (schema.type === 'object') {
            return `<fieldset><legend>${escapeHtml(key)}</legend>${Object.entries(schema.properties).map(([childKey, child]) => fieldHtml(childKey, child, value?.[childKey], inputName)).join('')}</fieldset>`;
        }
        if (schema.type === 'boolean') return `<div class="field">${label}<select id="field-${escapeHtml(inputName)}" data-path="${escapeHtml(inputName)}" data-type="boolean"><option value="true" ${value === true ? 'selected' : ''}>true</option><option value="false" ${value === false ? 'selected' : ''}>false</option></select></div>`;
        if (schema.type === 'array' || schema.type === 'mixed') return `<div class="field">${label}<textarea id="field-${escapeHtml(inputName)}" data-path="${escapeHtml(inputName)}" data-type="json" rows="5">${escapeHtml(JSON.stringify(value ?? (schema.type === 'array' ? [] : null), null, 2))}</textarea><small>Enter valid JSON.</small></div>`;
        const htmlType = schema.type === 'number' ? 'number' : 'text';
        return `<div class="field">${label}<input id="field-${escapeHtml(inputName)}" data-path="${escapeHtml(inputName)}" data-type="${escapeHtml(schema.type)}" type="${htmlType}" value="${escapeHtml(value ?? '')}"></div>`;
    }

    function recordSchema() { return state.schema?.type === 'array' ? state.schema.items : state.schema; }

    function openEditor(index = null) {
        state.editingIndex = index;
        const value = index === null ? {} : clone(state.records[index]);
        const schema = recordSchema();
        $('#dialogTitle').textContent = index === null ? 'New item' : `Edit item #${index + 1}`;
        if (!schema || schema.type === 'unknown' || schema.type !== 'object') {
            fields.innerHTML = `<div class="field"><label for="rawRecord">Item as JSON object</label><textarea id="rawRecord" data-raw-record rows="12">${escapeHtml(JSON.stringify(value, null, 2))}</textarea></div>`;
        } else {
            fields.innerHTML = Object.entries(schema.properties).map(([key, child]) => fieldHtml(key, child, value?.[key], '')).join('');
        }
        dialog.showModal();
    }

    function setDeep(target, path, value) {
        const parts = path.split('.');
        let cursor = target;
        parts.forEach((part, index) => {
            if (index === parts.length - 1) cursor[part] = value;
            else cursor = cursor[part] ||= {};
        });
    }

    function readForm() {
        const raw = fields.querySelector('[data-raw-record]');
        if (raw) {
            const parsed = JSON.parse(raw.value);
            if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) throw new Error('The item must be a JSON object.');
            return parsed;
        }
        const record = {};
        fields.querySelectorAll('[data-path]').forEach(input => {
            let value = input.value;
            if (input.dataset.type === 'number') value = value === '' ? null : Number(value);
            else if (input.dataset.type === 'boolean') value = value === 'true';
            else if (input.dataset.type === 'null') value = null;
            else if (input.dataset.type === 'json') value = JSON.parse(value);
            setDeep(record, input.dataset.path, value);
        });
        return record;
    }

    function syncData() {
        if (state.collectionKey === null) state.data = state.records;
        else if (state.collectionKey === '__root_object__') state.data = state.records[0] || {};
        else state.data[state.collectionKey] = state.records;
    }

    fileInput.addEventListener('change', async event => {
        const file = event.target.files[0];
        if (!file) return;
        try { loadData(JSON.parse(await file.text()), file.name); }
        catch (error) { workspace.hidden = true; notice.textContent = `Could not load the file: ${error.message}`; notice.className = 'notice notice-error'; }
        event.target.value = '';
    });

    $('#collectionSelect').addEventListener('change', event => selectCollection(event.target.value));
    $('#recordSearch').addEventListener('input', renderRecords);
    $('#newRecordButton').addEventListener('click', () => openEditor());
    document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => dialog.close()));

    form.addEventListener('submit', event => {
        event.preventDefault();
        try {
            const record = readForm();
            if (state.editingIndex === null) state.records.push(record); else state.records[state.editingIndex] = record;
            syncData(); renderAll(); dialog.close(); notice.textContent = 'Item saved. Download the JSON when you are ready.'; notice.className = 'notice notice-success';
        } catch (error) { alert(`Please check the form: ${error.message}`); }
    });

    recordsList.addEventListener('click', event => {
        const edit = event.target.closest('[data-edit]');
        if (edit) openEditor(Number(edit.dataset.edit));
        const remove = event.target.closest('[data-delete]');
        if (remove && confirm('Delete this item? This action can only be undone by reloading the original file.')) {
            state.records.splice(Number(remove.dataset.delete), 1); syncData(); renderAll();
        }
    });

    document.querySelectorAll('.tab').forEach(tab => tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(item => item.classList.toggle('is-active', item === tab));
        $('#structurePanel').hidden = tab.dataset.tab !== 'structure';
        $('#recordsPanel').hidden = tab.dataset.tab !== 'records';
    }));

    $('#expandStructure').addEventListener('click', event => {
        const details = [...structureTree.querySelectorAll('details')];
        const shouldOpen = details.some(item => !item.open);
        details.forEach(item => item.open = shouldOpen);
        event.target.textContent = shouldOpen ? 'Collapse all' : 'Expand all';
    });

    $('#downloadButton').addEventListener('click', () => {
        syncData();
        const blob = new Blob([JSON.stringify(state.data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a'); anchor.href = url; anchor.download = state.fileName.replace(/\.json$/i, '') + '-updated.json'; anchor.click();
        setTimeout(() => URL.revokeObjectURL(url), 0);
    });
})();
