import { createTs } from '@shared/tom-select-factory.js';
import { makeWizardController } from '@shared/wizard-controller.js';

const FORM_SEL = '[data-sales-package-form]';

function validateStep1() {
    if (!this.customer_id) {
        window.Toast?.warning('Vui lòng chọn khách hàng.', { duration: 4000 });
        return false;
    }
    return true;
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('salesPackageWizard', (serverData = {}) => ({
        ...makeWizardController({
            steps: ['Chọn khách hàng', 'Chọn thành phần hồ sơ', 'Thông tin gói'],
            validators: [validateStep1, null, null],
            onStepChange(step) {
                if (step === 3) this.$nextTick(() => this._initStatusSelect());
            },
        }),

        customer_id: serverData.customerId ?? '',
        name: serverData.name ?? '',
        notes: serverData.notes ?? '',
        submitting: false,

        loading: false,
        items: [],
        selectedIds: [],
        total: 0,
        satisfiedCount: 0,
        score: 0,
        customerName: '',

        customDocuments: [],
        customDocDraft: { name: '', files: [] },

        init() {
            const errs = serverData.errors ?? [];
            const hasDocError = errs.some((e) => e.startsWith('document_ids') || e.startsWith('custom_documents'));
            if (hasDocError) {
                this.currentStep = 2;
            } else if (errs.includes('name') || errs.includes('expected_deadline') || errs.includes('status')) {
                this.currentStep = 3;
                this.$nextTick(() => this._initStatusSelect());
            } else if (errs.includes('customer_id')) {
                this.currentStep = 1;
            }

            if (this.customer_id) this.loadChecklist(this.customer_id);
        },

        _initStatusSelect() {
            const el = document.querySelector('#ts-status');
            if (!el || el.tomselect) return;
            createTs(el, { placeholder: el.dataset.tsPlaceholder || 'Chọn trạng thái' });
        },

        get groupedItems() {
            const groups = {};
            for (const item of this.items) {
                const key = item.document_group_label || 'Khác';
                (groups[key] ??= []).push(item);
            }
            return groups;
        },

        get previewItems() {
            const checked = this.items
                .filter((i) => this.selectedIds.includes(i.compliance_document_id))
                .map((i) => ({ label: i.label, is_expiring_soon: i.is_expiring_soon, media_count: i.media_count ?? 0 }));

            const custom = this.customDocuments.map((d) => ({ label: d.name, is_expiring_soon: false, media_count: d.files.length }));

            return [...checked, ...custom];
        },

        openCustomDocModal() {
            this.customDocDraft = { name: '', files: [] };
            if (this.$refs.customDocFileInput) this.$refs.customDocFileInput.value = '';
            this.$refs.customDocModal.showModal();
        },

        onCustomDocFilesChange(event) {
            this.customDocDraft.files = Array.from(event.target.files ?? []);
        },

        removeCustomDocDraftFile(index) {
            this.customDocDraft.files.splice(index, 1);

            // FileList is immutable — rebuild it via DataTransfer so the
            // <input> only submits the files still left in the draft.
            const dt = new DataTransfer();
            this.customDocDraft.files.forEach((file) => dt.items.add(file));
            this.$refs.customDocFileInput.files = dt.files;
        },

        addCustomDocument() {
            const files = this.customDocDraft.files;

            if (!this.customDocDraft.name.trim() || files.length === 0) {
                window.Toast?.warning('Vui lòng nhập tên và chọn ít nhất 1 file.', { duration: 4000 });
                return;
            }

            const id = (window.crypto?.randomUUID?.() ?? String(Date.now() + Math.random()));
            this.customDocuments.push({ id, name: this.customDocDraft.name.trim(), files: files.slice() });

            this.$nextTick(() => {
                const input = document.querySelector('[data-custom-doc-id="' + id + '"]');
                if (input) {
                    const dt = new DataTransfer();
                    files.forEach((file) => dt.items.add(file));
                    input.files = dt.files;
                }
            });

            this.$refs.customDocModal.close();
        },

        removeCustomDocument(index) {
            this.customDocuments.splice(index, 1);
        },

        async loadChecklist(customerId) {
            if (!customerId) return;
            this.loading = true;
            this.items = [];
            this.selectedIds = [];

            const url = serverData.checklistUrlTemplate.replace('__ID__', customerId);
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();

            this.customerName = data.customer?.name ?? '';
            this.items = data.items ?? [];
            this.total = data.total ?? 0;
            this.satisfiedCount = data.satisfied_count ?? 0;
            this.score = data.score ?? 0;

            if (serverData.existingDocumentIds) {
                this.selectedIds = this.items
                    .filter((i) => serverData.existingDocumentIds.includes(i.compliance_document_id))
                    .map((i) => i.compliance_document_id);
            } else {
                this.selectedIds = this.items
                    .filter((i) => i.satisfied && i.compliance_document_id)
                    .map((i) => i.compliance_document_id);
            }

            const isEditMode = Object.prototype.hasOwnProperty.call(serverData, 'packageExpectedDeadline');
            _fillExpectedDeadline(isEditMode ? serverData.packageExpectedDeadline : (data.expected_deadline ?? null));

            this.loading = false;
        },

        handleSubmit() {
            if (!this.name.trim()) return;
            this.submitting = true;
        },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    window.initFormValidation?.(FORM_SEL);
    window.initAllDatePickers?.(form);
    _initCustomerSelect(form);
});

function _fillExpectedDeadline(value) {
    const el = document.querySelector('#fp-expected-deadline');
    if (!el) return;

    if (el._flatpickr) {
        if (value) el._flatpickr.setDate(value, true);
        else el._flatpickr.clear();
    } else {
        el.value = value ?? '';
    }
}

function _initCustomerSelect(form) {
    const el = form.querySelector('#ts-customer_id');
    if (!el) return;

    createTs(el, {
        placeholder: el.dataset.tsPlaceholder || '— Chọn khách hàng —',
        onChange(value) {
            const wrapper = form.closest('[x-data]') ?? document.querySelector('[x-data]');
            const data = window.Alpine?.$data(wrapper);
            if (!data) return;
            data.customer_id = value;
            data.loadChecklist(value);
        },
    });
}
