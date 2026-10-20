const EXTENSIONS = ['xls', 'xlsx'];
const FORM_OVERHEAD_BYTES = 64 * 1024;

const mb = (bytes) => (Math.round(bytes / 1024 / 1024 * 10) / 10).toLocaleString('vi-VN');

function summaryMessage(results) {
    const count = (status) => results.filter((r) => r.status === status).length;
    const newProducts = results.flatMap((r) => r.new_products ?? []);
    let message = `Import thành công ${count('imported')} phiếu.`;
    if (count('duplicate') > 0) message += ` Bỏ qua ${count('duplicate')} phiếu trùng lặp.`;
    if (newProducts.length > 0) {
        message += ` Đã tự động tạo mới ${newProducts.length} sản phẩm chưa có trong danh mục: ${newProducts.join(', ')}.`;
    }
    if (count('failed') > 0) message += ` Lỗi ${count('failed')} file.`;
    return message;
}

function failed(file, message) {
    return { file_name: file.name, status: 'failed', misa_ref_id: null, message, items_count: 0, new_products: [] };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('salesOrderImport', ({ url, limits = {}, summary = null }) => {
        const maxFiles = Math.max(1, limits.maxFiles || 20);
        const maxPostBytes = limits.maxPostBytes || 0;
        const maxFileBytes = limits.maxFileBytes || 10 * 1024 * 1024;
        const budget = maxPostBytes > 0 ? Math.max(maxPostBytes - FORM_OVERHEAD_BYTES, 1) : Infinity;

        return {
            summary,
            selectedCount: 0,
            submitting: false,
            progress: '',
            error: '',

            onFilesChange(event) {
                this.selectedCount = event.target.files.length;
                this.error = '';
            },

            precheck(file) {
                const ext = file.name.split('.').pop().toLowerCase();
                if (!EXTENSIONS.includes(ext)) return failed(file, 'Sai định dạng — chỉ chấp nhận file Excel .xls, .xlsx.');
                if (file.size > maxFileBytes) return failed(file, `File ${mb(file.size)} MB vượt quá dung lượng cho phép (${mb(maxFileBytes)} MB).`);
                if (file.size + FORM_OVERHEAD_BYTES > budget) return failed(file, `File ${mb(file.size)} MB vượt quá giới hạn gửi lên của máy chủ.`);
                return null;
            },

            chunk(files) {
                const chunks = [];
                let current = [];
                let size = 0;
                files.forEach((file) => {
                    if (current.length > 0 && (current.length >= maxFiles || size + file.size > budget)) {
                        chunks.push(current);
                        current = [];
                        size = 0;
                    }
                    current.push(file);
                    size += file.size;
                });
                if (current.length > 0) chunks.push(current);
                return chunks;
            },

            async send(files, form) {
                const body = new FormData();
                body.append('_token', form.querySelector('input[name=_token]').value);
                body.append('delivery_date', form.querySelector('[name=delivery_date]').value);
                files.forEach((file) => {
                    body.append('files[]', file, file.name);
                    body.append('file_names[]', file.name);
                });

                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });
                const data = await res.json().catch(() => ({}));

                if (res.status === 422) {
                    const err = new Error(Object.values(data.errors ?? {})[0]?.[0] ?? 'Dữ liệu không hợp lệ.');
                    err.validation = true;
                    throw err;
                }
                if (res.status === 413) throw new Error('Dung lượng các file vượt quá giới hạn của máy chủ.');
                if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);

                return data.results ?? [];
            },

            async submit(event) {
                const form = event.target;
                const files = Array.from(form.querySelector('input[type=file]').files);
                this.error = '';
                if (files.length === 0) {
                    this.error = 'Vui lòng chọn ít nhất 1 file Excel.';
                    return;
                }
                if (!form.querySelector('[name=delivery_date]').value) {
                    this.error = 'Vui lòng chọn ngày giao hàng.';
                    return;
                }

                const results = new Array(files.length).fill(null);
                const queue = [];
                files.forEach((file, index) => {
                    const rejected = this.precheck(file);
                    if (rejected) results[index] = rejected;
                    else queue.push({ file, index });
                });

                const chunks = this.chunk(queue.map((q) => q.file));
                this.submitting = true;
                let offset = 0;
                try {
                    for (let i = 0; i < chunks.length; i++) {
                        const part = queue.slice(offset, offset + chunks[i].length);
                        offset += chunks[i].length;
                        this.progress = `Đang xử lý lượt ${i + 1}/${chunks.length} (${part.length} file)…`;
                        try {
                            const received = await this.send(chunks[i], form);
                            part.forEach((q, j) => { results[q.index] = received[j] ?? failed(q.file, 'Máy chủ không trả kết quả cho file này.'); });
                            received.slice(part.length).forEach((r) => results.push(r));
                        } catch (e) {
                            if (e.validation && i === 0) {
                                this.error = e.message;
                                return;
                            }
                            part.forEach((q) => { results[q.index] = failed(q.file, 'Tải lên thất bại: ' + e.message); });
                        }
                    }

                    const list = results.filter(Boolean);
                    this.summary = { total_files: list.length, summary_message: summaryMessage(list), results: list };
                    form.querySelector('input[type=file]').value = '';
                    this.selectedCount = 0;
                    this.$nextTick(() => this.$refs.summary?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
                } finally {
                    this.submitting = false;
                    this.progress = '';
                }
            },
        };
    });
});
