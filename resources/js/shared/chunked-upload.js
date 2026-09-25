const CHUNK_UPLOAD_URL = '/api/v1/media/chunk-upload';
const CHUNK_SIZE = 1024 * 1024;
const MAX_FILE_MB = 100;

function _tooLargeMessage(file) {
    const sizeMb = (file.size / 1024 / 1024).toLocaleString('vi-VN', { maximumFractionDigits: 1 });
    return `File "${file.name}" có dung lượng ${sizeMb} MB, vượt quá giới hạn tối đa ${MAX_FILE_MB} MB. Vui lòng nén hoặc chia nhỏ file trước khi tải lên.`;
}

async function _uploadFileInChunks(file, csrf, onProgress) {
    const uploadId = crypto.randomUUID?.() ?? '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, (c) =>
        (c ^ (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))).toString(16));
    const total = Math.max(1, Math.ceil(file.size / CHUNK_SIZE));

    for (let index = 0; index < total; index++) {
        const body = new FormData();
        body.append('upload_id', uploadId);
        body.append('chunk_index', index);
        body.append('total_chunks', total);
        body.append('file_name', file.name);
        body.append('file_size', file.size);
        body.append('chunk', file.slice(index * CHUNK_SIZE, (index + 1) * CHUNK_SIZE), file.name);

        const res = await fetch(CHUNK_UPLOAD_URL, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            throw new Error(firstError || data.message || `Tải lên "${file.name}" thất bại.`);
        }

        onProgress(Math.min(file.size, (index + 1) * CHUNK_SIZE));
        if (data.done) return data.token;
    }

    throw new Error(`Tải lên "${file.name}" chưa hoàn tất.`);
}

export function bindChunkedSubmit(form) {
    if (form._chunkedSubmitBound) return;
    form._chunkedSubmitBound = true;

    form.addEventListener('submit', async (event) => {
        const fileInputs = Array.from(form.querySelectorAll('input[type="file"][name="files[]"], input[type="file"][name="file"]'));
        const entries = fileInputs.flatMap((input) => Array.from(input.files ?? []).map((file) => ({
            file,
            field: input.name === 'file' ? 'uploaded_file' : 'uploaded_files[]',
        })));
        const files = entries.map((e) => e.file);
        if (!files.length) return;

        event.preventDefault();
        if (form._chunkedUploading) return;

        const tooLarge = files.filter((f) => f.size > MAX_FILE_MB * 1024 * 1024);
        if (tooLarge.length) {
            alert(tooLarge.map(_tooLargeMessage).join('\n'));
            return;
        }
        form._chunkedUploading = true;

        const submitEl = form.querySelector('[type="submit"]');
        const originalLabel = submitEl?.textContent;
        if (submitEl) submitEl.disabled = true;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        const totalBytes = files.reduce((sum, f) => sum + f.size, 0) || 1;
        let doneBytes = 0;
        const tokens = [];

        try {
            for (const { file, field } of entries) {
                const token = await _uploadFileInChunks(file, csrf, (sent) => {
                    if (submitEl) submitEl.textContent = `Đang tải lên ${Math.floor(((doneBytes + sent) / totalBytes) * 100)}%`;
                });
                doneBytes += file.size;
                tokens.push({ token, field });
            }
        } catch (e) {
            alert(e.message || 'Lỗi kết nối. Vui lòng thử lại.');
            if (submitEl) {
                submitEl.disabled = false;
                submitEl.textContent = originalLabel;
            }
            form._chunkedUploading = false;
            return;
        }

        form.querySelectorAll('input[name="uploaded_files[]"], input[name="uploaded_file"]').forEach((el) => el.remove());
        tokens.forEach(({ token, field }) => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = field;
            hidden.value = token;
            form.appendChild(hidden);
        });
        fileInputs.forEach((input) => { input.disabled = true; });

        if (submitEl) submitEl.textContent = 'Đang lưu...';
        HTMLFormElement.prototype.submit.call(form);
    });
}
