(() => {
    const state = { original: null, data: null, fileName: 'data.json', collections: [], collectionKey: null, records: [], schema: null, validationSchema: null, schemaReference: null, validationErrors: [], editingIndex: null };
    const $ = (selector) => document.querySelector(selector);
    const fileInput = $('#jsonFile');
    const notice = $('#notice');
    const schemaNotice = $('#schemaNotice');
    const workspace = $('#workspace');
    const structureTree = $('#structureTree');
    const recordsList = $('#recordsList');
    const dialog = $('#recordDialog');
    const form = $('#recordForm');
    const fields = $('#dynamicFields');

    const typeOf = (value) => value === null ? 'null' : Array.isArray(value) ? 'array' : typeof value;
    const clone = (value) => JSON.parse(JSON.stringify(value));
    const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));

    function valueAtPointer(document, pointer) {
        if (pointer === '#') return document;
        if (!pointer.startsWith('#/')) return undefined;
        return pointer.slice(2).split('/').reduce((value, part) => value?.[part.replace(/~1/g, '/').replace(/~0/g, '~')], document);
    }

    function matchesType(value, type) {
        if (type === 'null') return value === null;
        if (type === 'array') return Array.isArray(value);
        if (type === 'object') return value !== null && typeof value === 'object' && !Array.isArray(value);
        if (type === 'integer') return Number.isInteger(value);
        if (type === 'number') return typeof value === 'number' && Number.isFinite(value);
        return typeof value === type;
    }

    function validateWithSchema(value, schema, rootSchema, path = '$', errors = []) {
        if (schema === true) return errors;
        if (schema === false) { errors.push(`${path}: value is not allowed.`); return errors; }
        if (!schema || typeof schema !== 'object') return errors;

        if (schema.$ref) {
            const referenced = valueAtPointer(rootSchema, schema.$ref);
            if (referenced === undefined) errors.push(`${path}: schema reference ${schema.$ref} was not found.`);
            else validateWithSchema(value, referenced, rootSchema, path, errors);
            return errors;
        }

        const allowedTypes = Array.isArray(schema.type) ? schema.type : schema.type ? [schema.type] : [];
        if (allowedTypes.length && !allowedTypes.some(type => matchesType(value, type))) {
            errors.push(`${path}: expected ${allowedTypes.join(' or ')}, received ${typeOf(value)}.`);
            return errors;
        }
        if (schema.const !== undefined && JSON.stringify(value) !== JSON.stringify(schema.const)) errors.push(`${path}: value must match the required constant.`);
        if (schema.enum && !schema.enum.some(item => JSON.stringify(item) === JSON.stringify(value))) errors.push(`${path}: value is not in the allowed list.`);

        if (typeof value === 'string') {
            if (schema.minLength !== undefined && value.length < schema.minLength) errors.push(`${path}: must contain at least ${schema.minLength} characters.`);
            if (schema.maxLength !== undefined && value.length > schema.maxLength) errors.push(`${path}: must contain at most ${schema.maxLength} characters.`);
            if (schema.pattern) {
                try { if (!(new RegExp(schema.pattern)).test(value)) errors.push(`${path}: does not match the required pattern.`); }
                catch { errors.push(`${path}: the schema contains an invalid pattern.`); }
            }
            if (schema.format === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) errors.push(`${path}: must be a valid email address.`);
            if (schema.format === 'date' && !/^\d{4}-\d{2}-\d{2}$/.test(value)) errors.push(`${path}: must use the YYYY-MM-DD date format.`);
            if (schema.format === 'date-time' && Number.isNaN(Date.parse(value))) errors.push(`${path}: must be a valid date and time.`);
        }

        if (typeof value === 'number') {
            if (schema.minimum !== undefined && value < schema.minimum) errors.push(`${path}: must be at least ${schema.minimum}.`);
            if (schema.maximum !== undefined && value > schema.maximum) errors.push(`${path}: must be at most ${schema.maximum}.`);
            if (schema.exclusiveMinimum !== undefined && value <= schema.exclusiveMinimum) errors.push(`${path}: must be greater than ${schema.exclusiveMinimum}.`);
            if (schema.exclusiveMaximum !== undefined && value >= schema.exclusiveMaximum) errors.push(`${path}: must be less than ${schema.exclusiveMaximum}.`);
        }

        if (Array.isArray(value)) {
            if (schema.minItems !== undefined && value.length < schema.minItems) errors.push(`${path}: must contain at least ${schema.minItems} items.`);
            if (schema.maxItems !== undefined && value.length > schema.maxItems) errors.push(`${path}: must contain at most ${schema.maxItems} items.`);
            if (schema.uniqueItems && new Set(value.map(item => JSON.stringify(item))).size !== value.length) errors.push(`${path}: items must be unique.`);
            if (schema.items) value.forEach((item, index) => validateWithSchema(item, schema.items, rootSchema, `${path}[${index}]`, errors));
        }

        if (value && typeof value === 'object' && !Array.isArray(value)) {
            (schema.required || []).forEach(key => { if (!(key in value)) errors.push(`${path}.${key}: required field is missing.`); });
            Object.entries(schema.properties || {}).forEach(([key, childSchema]) => {
                if (key in value) validateWithSchema(value[key], childSchema, rootSchema, `${path}.${key}`, errors);
            });
            if (schema.additionalProperties === false) {
                Object.keys(value).filter(key => !(key in (schema.properties || {}))).forEach(key => errors.push(`${path}.${key}: field is not allowed.`));
            }
        }
        (schema.allOf || []).forEach(childSchema => validateWithSchema(value, childSchema, rootSchema, path, errors));
        if (schema.anyOf && !schema.anyOf.some(childSchema => validateWithSchema(value, childSchema, rootSchema, path, []).length === 0)) errors.push(`${path}: does not match any allowed schema.`);
        if (schema.oneOf && schema.oneOf.filter(childSchema => validateWithSchema(value, childSchema, rootSchema, path, []).length === 0).length !== 1) errors.push(`${path}: must match exactly one allowed schema.`);
        return errors;
    }

    function renderSchemaStatus() {
        schemaNotice.hidden = false;
        if (!state.schemaReference) {
            schemaNotice.className = 'notice notice-warning';
            schemaNotice.innerHTML = '<strong>No JSON Schema declared.</strong> The editor can check JSON syntax and inferred field types, but it cannot prevent values that break project-specific rules.';
            return;
        }
        if (!state.validationSchema) {
            schemaNotice.className = 'notice notice-error';
            schemaNotice.innerHTML = `<strong>Schema not available.</strong> The file declares <code>${escapeHtml(state.schemaReference)}</code>, but it could not be loaded. Project-specific formats cannot be enforced.`;
            return;
        }
        state.validationErrors = validateWithSchema(state.data, state.validationSchema, state.validationSchema);
        if (!state.validationErrors.length) {
            schemaNotice.className = 'notice notice-success';
            schemaNotice.innerHTML = `<strong>JSON Schema active.</strong> The content follows <code>${escapeHtml(state.schemaReference)}</code>.`;
            return;
        }
        schemaNotice.className = 'notice notice-error';
        schemaNotice.innerHTML = `<strong>Schema validation failed.</strong><ul class="validation-errors">${state.validationErrors.slice(0, 8).map(error => `<li>${escapeHtml(error)}</li>`).join('')}</ul>${state.validationErrors.length > 8 ? `<p>${state.validationErrors.length - 8} more errors were found.</p>` : ''}`;
    }

    async function loadDeclaredSchema(data) {
        state.schemaReference = typeof data?.$schema === 'string' ? data.$schema : null;
        state.validationSchema = null;
        state.validationErrors = [];
        if (!state.schemaReference) { renderSchemaStatus(); return; }
        try {
            const response = await fetch(state.schemaReference, { headers: { Accept: 'application/schema+json, application/json' } });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const schema = await response.json();
            if (!schema || typeof schema !== 'object' || Array.isArray(schema)) throw new Error('Invalid schema document');
            state.validationSchema = schema;
        } catch (error) {
            console.warn('Could not load the declared JSON Schema.', error);
        }
        renderSchemaStatus();
    }

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

    async function loadData(data, name) {
        if (!data || (typeof data !== 'object')) throw new Error('The JSON root must be an object or an array.');
        state.original = clone(data);
        state.data = data;
        state.fileName = name || 'data.json';
        await loadDeclaredSchema(data);
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
        try { await loadData(JSON.parse(await file.text()), file.name); }
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
            const previousRecords = clone(state.records);
            if (state.editingIndex === null) state.records.push(record); else state.records[state.editingIndex] = record;
            syncData();
            if (state.validationSchema && validateWithSchema(state.data, state.validationSchema, state.validationSchema).length) {
                state.records.splice(0, state.records.length, ...previousRecords); syncData(); renderSchemaStatus();
                throw new Error('The item does not follow the declared JSON Schema.');
            }
            renderAll(); renderSchemaStatus(); dialog.close(); notice.textContent = 'Item saved. Download the JSON when you are ready.'; notice.className = 'notice notice-success';
        } catch (error) { alert(`Please check the form: ${error.message}`); }
    });

    recordsList.addEventListener('click', event => {
        const edit = event.target.closest('[data-edit]');
        if (edit) openEditor(Number(edit.dataset.edit));
        const remove = event.target.closest('[data-delete]');
        if (remove && confirm('Delete this item? This action can only be undone by reloading the original file.')) {
            const removedIndex = Number(remove.dataset.delete);
            const removed = state.records.splice(removedIndex, 1)[0]; syncData();
            if (state.validationSchema && validateWithSchema(state.data, state.validationSchema, state.validationSchema).length) {
                state.records.splice(removedIndex, 0, removed); syncData(); renderSchemaStatus();
                alert('This item cannot be deleted because the result would break the declared JSON Schema.');
                return;
            }
            renderAll(); renderSchemaStatus();
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
        renderSchemaStatus();
        if (state.validationSchema && state.validationErrors.length) {
            alert('The JSON cannot be downloaded until the schema validation errors are fixed.');
            return;
        }
        const blob = new Blob([JSON.stringify(state.data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a'); anchor.href = url; anchor.download = state.fileName.replace(/\.json$/i, '') + '-updated.json'; anchor.click();
        setTimeout(() => URL.revokeObjectURL(url), 0);
    });
})();
