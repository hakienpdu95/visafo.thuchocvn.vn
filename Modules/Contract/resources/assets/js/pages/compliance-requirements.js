const BLANK = {
    group:        null,
    kind:         'document',
    target_ids:   [],
    label:        '',
    source_group: '',
    is_mandatory: true,
    warning_days: 30,
    legal_basis:  '',
};

document.addEventListener('alpine:init', () => {

    Alpine.data('complianceRequirementPage', (serverData = {}) => {
        const { options = {}, storeUrl = '', updateUrl = '', old = null } = serverData;

        return {
            storeUrl,
            updateUrl,
            form: { ...BLANK },
            labelTouched: false,

            get currentOptions() {
                return options[this.form.kind] || [];
            },

            init() {
                if (old) {
                    this.form = {
                        ...BLANK,
                        ...old,
                        group:        old.group || null,
                        source_group: old.source_group || '',
                        target_ids:   (old.target_ids || []).map(String),
                    };
                    this.labelTouched = true;
                    this.$nextTick(() => this.$refs.modal?.showModal());
                }
            },

            openCreate(sourceGroup = '') {
                this.form = { ...BLANK, target_ids: [], source_group: sourceGroup };
                this.labelTouched = false;
                this.$refs.modal.showModal();
            },

            openEdit(group) {
                this.form = {
                    group:        group.key,
                    kind:         group.kind,
                    target_ids:   [...group.target_ids],
                    label:        group.label,
                    source_group: group.source_group || '',
                    is_mandatory: !!group.is_mandatory,
                    warning_days: group.warning_days,
                    legal_basis:  group.legal_basis || '',
                };
                this.labelTouched = true;
                this.$refs.modal.showModal();
            },

            suggestLabel() {
                if (this.labelTouched) return;
                const names = this.currentOptions
                    .filter((o) => this.form.target_ids.includes(o.value))
                    .map((o) => o.text);
                this.form.label = names.join(' hoặc ');
            },
        };
    });
});
