import { createTs } from '@shared/tom-select-factory.js';

const BATCH_GROUPS = [
    { value: 'active',    label: 'Đang canh tác' },
    { value: 'harvested', label: 'Đã thu hoạch' },
];

function pinDropdown(ts) {
    const rect = ts.control.getBoundingClientRect();
    Object.assign(ts.dropdown.style, {
        position: 'fixed',
        top:      (rect.bottom + 4) + 'px',
        left:     rect.left + 'px',
        right:    'auto',
        width:    rect.width + 'px',
        margin:   0,
    });
}

async function postPreview(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN':     document.querySelector('meta[name=csrf-token]')?.content ?? '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept':           'application/json',
        },
        body,
    });
    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        throw new Error(data.errors?.file?.[0] ?? data.errors?.token?.[0] ?? data.message ?? 'Không đọc được file. Vui lòng thử lại.');
    }

    return data;
}

function discardImport(url, token) {
    fetch(url.replace('__TOKEN__', token), {
        method:    'DELETE',
        keepalive: true,
        headers: {
            'X-CSRF-TOKEN':     document.querySelector('meta[name=csrf-token]')?.content ?? '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept':           'application/json',
        },
    }).catch(() => {});
}

document.addEventListener('alpine:init', () => {
    Alpine.data('farmingLogImport', (serverData = {}) => ({
        previewUrl: serverData.previewUrl ?? '',
        discardUrl: serverData.discardUrl ?? '',
        createBatchUrl: serverData.createBatchUrl ?? '',
        step: 'upload',
        submitted: false,
        loading: false,
        error: '',
        token: '',
        sheets: [],
        batches: [],
        mapping: {},

        get selectedCount() {
            return Object.values(this.mapping).filter(Boolean).length;
        },

        get canSubmit() {
            return this.selectedCount > 0 && !this.sheets.some((s) => this.isDuplicate(s.name));
        },

        batchesByStatus(status) {
            return this.batches.filter((b) => b.status === status);
        },

        initMappingSelect(el) {
            const sheetName = el.dataset.sheet;
            const options = [
                { value: '', text: '— Bỏ qua sheet này —', optgroup: null },
                ...this.batches.map((b) => ({ value: b.id, text: b.label, optgroup: b.status })),
            ];

            if (el.tomselect) {
                el.tomselect.clearOptions();
                el.tomselect.addOptions(options);
                el.tomselect.setValue(this.mapping[sheetName] ?? '', true);
                return;
            }

            const ts = createTs(el, {
                dropdownParent:   this.$root,
                options,
                optgroups:        BATCH_GROUPS,
                optgroupField:    'optgroup',
                valueField:       'value',
                labelField:       'text',
                searchField:      ['text'],
                allowEmptyOption: true,
                placeholder:      '— Bỏ qua sheet này —',
                items:            [this.mapping[sheetName] ?? ''],
                onChange: (value) => { this.mapping[sheetName] = value ?? ''; },
            });
            ts?.on('dropdown_open', () => pinDropdown(ts));
        },

        syncMappingSelects() {
            this.$nextTick(() => {
                this.$root.querySelectorAll('select[data-sheet]').forEach((el) => this.initMappingSelect(el));
            });
        },

        isSuggested(sheet) {
            return !!sheet.suggestion && this.mapping[sheet.name] === sheet.suggestion.batch_id;
        },

        isLooseMatch(sheet) {
            return this.isSuggested(sheet) && sheet.suggestion.level === 'owner_crop';
        },

        createBatchLink(sheet) {
            return this.createBatchUrl + (sheet.sowing_date ? '&sowing_date=' + encodeURIComponent(sheet.sowing_date) : '');
        },

        isDuplicate(sheetName) {
            const id = this.mapping[sheetName];
            if (!id) return false;
            return Object.entries(this.mapping).some(([name, other]) => name !== sheetName && other === id);
        },

        discard() {
            if (this.token && !this.submitted) discardImport(this.discardUrl, this.token);
            this.reset();
        },

        reset() {
            this.$root.querySelectorAll('select[data-sheet]').forEach((el) => el.tomselect?.destroy());
            this.submitted = false;
            this.step = 'upload';
            this.loading = false;
            this.error = '';
            this.token = '';
            this.sheets = [];
            this.mapping = {};
        },

        async preview(e) {
            await this._fetchPreview(new FormData(e.target), false);
        },

        async refresh() {
            const body = new FormData();
            body.append('token', this.token);
            await this._fetchPreview(body, true);
        },

        async _fetchPreview(body, keepManual) {
            this.loading = true;
            this.error = '';

            try {
                const data = await postPreview(this.previewUrl, body);

                const ids = new Set(data.batches.map((b) => b.id));
                const previous = keepManual ? { ...this.mapping } : {};

                this.token = data.token;
                this.batches = data.batches;
                this.sheets = data.sheets;
                this.mapping = Object.fromEntries(data.sheets.map((s) => [
                    s.name,
                    previous[s.name] && ids.has(previous[s.name]) ? previous[s.name] : (s.suggestion?.batch_id ?? ''),
                ]));
                this.step = 'map';
                if (keepManual) this.syncMappingSelects();
            } catch (err) {
                this.error = err.message || 'Lỗi kết nối. Vui lòng thử lại.';
            } finally {
                this.loading = false;
            }
        },
    }));

    Alpine.data('farmingBatchLogImport', (serverData = {}) => ({
        previewUrl: serverData.previewUrl ?? '',
        discardUrl: serverData.discardUrl ?? '',
        batchId: serverData.batchId ?? '',
        step: 'upload',
        loading: false,
        error: '',
        token: '',
        sheets: [],
        selectedSheet: '',
        submitted: false,

        matchesBatch(sheet) {
            return sheet.suggestion?.batch_id === this.batchId;
        },

        discard() {
            if (this.token && !this.submitted) discardImport(this.discardUrl, this.token);
            this.step = 'upload';
            this.loading = false;
            this.error = '';
            this.token = '';
            this.sheets = [];
            this.selectedSheet = '';
            this.submitted = false;
        },

        async preview(e) {
            this.loading = true;
            this.error = '';

            try {
                const data = await postPreview(this.previewUrl, new FormData(e.target));
                this.token = data.token;
                this.sheets = data.sheets;
                const matched = data.sheets.find((s) => this.matchesBatch(s));
                this.selectedSheet = data.sheets.length === 1 ? data.sheets[0].name : (matched?.name ?? '');
                this.step = 'pick';
            } catch (err) {
                this.error = err.message || 'Lỗi kết nối. Vui lòng thử lại.';
            } finally {
                this.loading = false;
            }
        },
    }));
});
