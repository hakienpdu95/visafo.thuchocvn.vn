@extends('layouts.backend')
@section('title', 'Gắn kết rời rạc — quét mã')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-base-content">Gắn kết rời rạc (Discrete Binding)</h1>
    <p class="text-sm text-base-content/50 mt-0.5">Dùng súng quét để dán bù từng tem lẻ — chọn lô hàng, sau đó quét liên tục.</p>
</div>

<div x-data="tagScanBind()" class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6 items-start">
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="form-control mb-4">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Lô hàng</span></label>
                <select x-model="batchId" class="select select-bordered select-sm w-full">
                    <option value="">— Chọn lô hàng —</option>
                    @foreach($batches as $batch)
                    <option value="{{ $batch->id }}">{{ $batch->internal_batch_code }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Quét mã QR (hoặc nhập uid)</span></label>
                <input type="text" x-ref="scanInput" x-model="scanValue"
                       @keydown.enter.prevent="submitScan()"
                       :disabled="!batchId"
                       placeholder="Chọn lô hàng trước, sau đó quét..."
                       class="input input-bordered input-lg w-full font-mono" autofocus>
            </div>

            <div class="mt-3 text-sm" :class="lastResult?.success ? 'text-success' : 'text-error'" x-show="lastResult" x-text="lastResult?.message"></div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-3">Lịch sử quét (<span x-text="log.length"></span>)</h2>
            <div class="space-y-1.5 max-h-[70vh] overflow-y-auto">
                <template x-for="(entry, i) in log" :key="i">
                    <div class="flex items-center justify-between text-xs py-1 border-b border-base-200">
                        <span x-text="entry.message" :class="entry.success ? 'text-success' : 'text-error'"></span>
                    </div>
                </template>
                <p x-show="log.length === 0" class="text-xs text-base-content/40">Chưa có lượt quét nào.</p>
            </div>
        </div>
    </div>
</div>

<script>
function tagScanBind() {
    return {
        batchId: '',
        scanValue: '',
        lastResult: null,
        log: [],
        submitScan() {
            const value = this.scanValue.trim();
            if (!value || !this.batchId) return;

            fetch('{{ route('backend.tag-scan-bind.scan') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ batch_id: this.batchId, scanned: value }),
            })
            .then(r => r.json())
            .then(data => {
                this.lastResult = data;
                this.log.unshift(data);
            })
            .catch(() => {
                this.lastResult = { success: false, message: 'Lỗi kết nối — thử lại.' };
                this.log.unshift(this.lastResult);
            })
            .finally(() => {
                this.scanValue = '';
                this.$refs.scanInput.focus();
            });
        },
    };
}
</script>
@endsection
