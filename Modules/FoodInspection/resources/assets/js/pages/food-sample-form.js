import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';
import './_row-directives.js';

const FORM_SEL = '[data-food-sample-form]';

let locationTs = null; // combobox "Địa điểm" — gợi ý theo cơ sở đang chọn, cho phép gõ tên mới

function refreshLocations(cfg, customerId, keep = '') {
    if (!locationTs) return;
    locationTs.clear(true);
    locationTs.clearOptions();
    (cfg.locations[customerId] ?? []).forEach((name) => locationTs.addOption({ value: name, text: name }));
    if (keep) { locationTs.addOption({ value: keep, text: keep }); locationTs.setValue(keep, true); }
}

// ── Alpine: form Sổ lưu & hủy mẫu thức ăn (2 nhịp) ─────────────────────────────
const MIN_RETENTION_MS = 24 * 60 * 60 * 1000;
const pad = (n) => String(n).padStart(2, '0');
const nowLocal = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`; };
const toDate = (s) => (s ? new Date(String(s).replace(' ', 'T')) : null);
const LIQUID = ['canh', 'súp', 'soup', 'cháo', 'nước', 'sữa', 'sinh tố', 'chè'];
// Gợi ý khối lượng mẫu tối thiểu: 100g (đặc) / 150ml (lỏng) — giống SampleVolume::suggest() phía server.
function suggestVolume(name) {
    const n = String(name ?? '').toLowerCase();
    if (n.includes('sữa chua')) return '100g';
    return LIQUID.some((k) => n.includes(k)) ? '150ml' : '100g';
}

document.addEventListener('alpine:init', () => {
    Alpine.data('foodSampleForm', (cfg) => {
        let seq = Math.max(0, ...cfg.rows.map((r) => r.n));
        const make = (src = {}) => ({
            n: src.n ?? ++seq,
            id: src.id ?? '',
            step3_detail_id: src.step3_detail_id ?? '',
            menu_dish_id: src.menu_dish_id ?? '',
            source: src.source ?? '',
            meal_time: src.meal_time ?? cfg.defaultMeal,
            dish_name: src.dish_name ?? '',
            portion_qty: src.portion_qty ?? '',
            sample_volume: src.sample_volume ?? suggestVolume(src.dish_name),
            volumeTouched: !!src.id,
            container_type: src.container_type ?? '',
            storage_temp: src.storage_temp ?? '4',
            sampled_at: src.sampled_at || nowLocal(),
            sampler_name: src.sampler_name ?? cfg.userName,
            destroyed_at: src.destroyed_at ?? '',
            destroyer_name: src.destroyer_name ?? '',
            quality_note: src.quality_note ?? '',
            // Mẫu đã hủy trong DB là bằng chứng pháp lý → bất biến (chỉ đọc), server cũng bỏ qua thay đổi.
            done: !!src.destroyed_at,
        });

        let ticker = null;

        return {
            isEdit: cfg.isEdit,
            meals: cfg.meals,
            containers: cfg.containers,
            rows: cfg.rows.map((r) => make(r)),
            customerId: cfg.customerId,
            nowTs: Date.now(),
            syncing: false,
            syncMessage: '',
            syncOk: true,
            submitting: false,

            init() {
                // Cập nhật mỗi phút để ô Hủy mẫu tự mở khóa khi đủ 24 giờ.
                ticker = setInterval(() => { this.nowTs = Date.now(); }, 60_000);
            },

            get destroyedCount() { return this.rows.filter((r) => r.destroyed_at).length; },
            get pendingCount() { return this.rows.filter((r) => !r.done && this.canDestroy(r)).length; },

            mealLabel(v) { return this.meals.find((m) => m.value === v)?.text ?? v; },
            // Dòng kế thừa từ Bước 3 / Thực đơn: tên món, ca/bữa, số suất chỉ đọc.
            locked(r) { return r.done || r.step3_detail_id !== '' || r.menu_dish_id !== ''; },

            // ── Nhịp 2: chỉ được hủy sau ≥ 24 giờ kể từ lúc lấy mẫu (chỉ ở chế độ sửa) ──
            destroyableAt(r) { const d = toDate(r.sampled_at); return d ? new Date(d.getTime() + MIN_RETENTION_MS) : null; },
            canDestroy(r) {
                if (!this.isEdit || r.done) return false;
                const at = this.destroyableAt(r);
                return !!at && this.nowTs >= at.getTime();
            },
            remainingLabel(r) {
                const at = this.destroyableAt(r);
                if (!at) return '';
                const mins = Math.max(0, Math.ceil((at.getTime() - this.nowTs) / 60000));
                return `Chưa đủ 24h lưu mẫu — còn ${mins >= 60 ? Math.floor(mins / 60) + ' giờ ' : ''}${mins % 60} phút`;
            },
            setDestroyNow(r) {
                if (!this.canDestroy(r)) return;
                r.destroyed_at = nowLocal();
                this.onDestroyedChange(r);
            },
            // Có giờ hủy → điền sẵn người hủy (người đang thao tác) và chất lượng "Đạt"; xóa giờ hủy → bỏ các trường đi kèm.
            onDestroyedChange(r) {
                if (r.destroyed_at) {
                    if (!r.destroyer_name) r.destroyer_name = cfg.userName;
                    if (!r.quality_note) r.quality_note = 'Đạt';
                } else {
                    r.destroyer_name = '';
                    r.quality_note = '';
                }
            },

            // ── Nhịp 1 ──
            setSampledNow(r) { r.sampled_at = nowLocal(); },
            onDishChange(r) { if (!r.volumeTouched) r.sample_volume = suggestVolume(r.dish_name); },
            tempOutOfRange(r) { const t = parseFloat(r.storage_temp); return r.storage_temp !== '' && !Number.isNaN(t) && (t < 2 || t > 8); },
            volumeTooSmall(r) {
                const m = String(r.sample_volume).trim().match(/^(\d+(?:[.,]\d+)?)\s*(g|ml|kg|l)$/i);
                if (!m) return false;
                const v = parseFloat(m[1].replace(',', '.'));
                const unit = m[2].toLowerCase();
                const base = unit === 'kg' || unit === 'l' ? v * 1000 : v;
                const liquid = unit === 'ml' || unit === 'l';
                return base < (liquid ? 150 : 100);
            },

            copyDown(i, field) {
                const value = this.rows[i]?.[field] ?? '';
                for (let k = i + 1; k < this.rows.length; k++) if (!this.rows[k].done) this.rows[k][field] = value;
            },
            applyAll(field) {
                if (!this.rows.length) return;
                if (!this.rows[0][field]) { window.Toast?.warning('Hãy nhập giá trị ở dòng 1 trước khi áp dụng cho tất cả.', { duration: 3500 }); return; }
                this.copyDown(0, field);
            },

            addRow() { this.rows.push(make()); },
            removeRow(r) { if (!r.done) this.rows = this.rows.filter((x) => x !== r); },

            onCustomerChange(id) {
                if (id === this.customerId) return;
                this.customerId = id;
                this.rows = this.rows.filter((r) => r.done || !this.locked(r));
                this.syncMessage = '';
                refreshLocations(cfg, id);
            },

            async syncSource() {
                const form = this.$root.closest('form') ?? this.$root;
                const date = form.querySelector('#fp-sample_date')?.value;
                const meal = form.querySelector('#ts-sync_meal')?.value;
                this.syncOk = false;

                if (!this.customerId) { this.syncMessage = 'Vui lòng chọn khách hàng trước khi lấy món.'; return; }
                if (!date) { this.syncMessage = 'Vui lòng chọn ngày lưu mẫu.'; return; }
                if (!meal) { this.syncMessage = 'Vui lòng chọn bữa ăn.'; return; }

                this.syncing = true;
                this.syncMessage = '';
                try {
                    const qs = new URLSearchParams({ customer_id: this.customerId, date, meal_time: meal });
                    const res = await fetch(cfg.syncUrl + '?' + qs, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();

                    if (!data.found) {
                        this.syncMessage = 'Chưa có món nào cho khách hàng, ngày và bữa ăn này — cần có Sổ Bước 3 hoặc Thực đơn tương ứng.';
                        return;
                    }

                    // Thay các dòng kế thừa (chưa hủy) của cùng bữa ăn; giữ dòng nhập tay, dòng đã hủy và các bữa khác.
                    this.rows = this.rows.filter((r) => r.done || !(this.locked(r) && r.meal_time === meal));
                    data.data.forEach((d) => this.rows.push(make({
                        step3_detail_id: d.step3_detail_id, menu_dish_id: d.menu_dish_id, source: d.source,
                        meal_time: d.meal_time, dish_name: d.dish_name, portion_qty: d.portion_qty, sample_volume: d.sample_volume,
                    })));
                    this.syncOk = true;
                    this.syncMessage = `Đã lấy ${data.data.length} món từ ${data.source === 'step3' ? 'Sổ Bước 3' : 'Thực đơn'}.`;
                } catch (e) {
                    console.error('[food-sample] sync failed', e);
                    this.syncMessage = 'Không lấy được danh sách món. Vui lòng thử lại.';
                } finally {
                    this.syncing = false;
                }
            },
        };
    });
});

// ── Entry point ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);        // global từ app.js
    window.initAllDatePickers?.(form);   // input.fp-init
    initAllTomSelects(form);             // select.ts-init (cơ sở, bữa ăn đồng bộ)
    _initCustomerAndLocation(form);
    _setupRowsGuard(form);
    _setupVisibleErrorToast(form);
    _setupSubmitLoading(form);
});

function _initCustomerAndLocation(form) {
    const cfg = JSON.parse(form.dataset.foodSampleConfig ?? '{}');
    const root = form.querySelector('[x-data]');
    const customerEl = form.querySelector('#ts-customer_id');
    const locationEl = form.querySelector('#ts-location_name');

    if (locationEl && !locationEl.tomselect) {
        locationTs = createTs(locationEl, {
            placeholder: 'Chọn hoặc gõ tên bếp ăn / địa điểm',
            create: (input) => ({ value: input.trim(), text: input.trim() }),
            createFilter: (input) => input.trim().length > 1,
            persist: false,
            render: { option_create: (data, escape) => '<div class="create">Thêm mới: <strong>' + escape(data.input) + '</strong>&hellip;</div>' },
        });
        refreshLocations(cfg, customerEl?.value ?? '', locationEl.dataset.initial ?? '');
    }

    customerEl?.tomselect?.on('change', (value) => {
        const data = root && window.Alpine?.$data(root);
        data?.onCustomerChange(value || '');
    });
}

// Chưa có món nào → chặn submit + Toast (conditional required, spec §17.6).
function _setupRowsGuard(form) {
    form.addEventListener('submit', (e) => {
        if (form.querySelector('[name^="details["]')) return;
        e.preventDefault();
        window.Toast?.warning('Cần ít nhất một món ăn để kiểm thực.', { duration: 4000 });
    }, true);
}

// initFormValidation (bubble) đã tô đỏ ô lỗi; bổ sung Toast (kèm số dòng) + focus ô lỗi đầu tiên.
function _setupVisibleErrorToast(form) {
    form.addEventListener('submit', (e) => {
        if (!e.defaultPrevented) return;
        requestAnimationFrame(() => {
            const bad = Array.from(form.querySelectorAll('.input-error, .select-error, .textarea-error'));
            if (!bad.length) return;
            const labels = [...new Set(bad.map((el) => el.dataset.label
                ?? el.closest('.form-control')?.querySelector('.label-text')?.textContent.replace(/\s*\*\s*$/, '').trim()
                ?? el.name))];
            window.Toast?.warning(`Vui lòng nhập đầy đủ:\n${labels.slice(0, 6).join('\n')}`, { duration: 5000 });
            bad[0].scrollIntoView?.({ behavior: 'smooth', block: 'center' });
            bad[0].focus?.({ preventScroll: true });
        });
    });
}

function _setupSubmitLoading(form) {
    form.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;
        const root = form.querySelector('[x-data]');
        const data = root && window.Alpine?.$data(root);
        if (data) data.submitting = true;
    });
}
