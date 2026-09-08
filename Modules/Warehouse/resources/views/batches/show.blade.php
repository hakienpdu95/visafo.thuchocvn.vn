@extends('layouts.backend')
@section('title', $batch->internal_batch_code)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2 font-mono">
            {{ $batch->internal_batch_code }}
            <span class="badge {{ $batch->status->badgeClass() }} badge-sm">{{ $batch->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $batch->product->name }} · {{ $batch->vendor->name }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.batches.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        <a href="{{ route('backend.inbound-receipts.show', $batch->inboundReceipt) }}" class="btn btn-ghost btn-sm">Xem phiếu nhập</a>
        @can('update', $batch)
        @if(($tagCounts['bound'] ?? 0) > 0)
        <form method="POST" action="{{ route('backend.batches.activate-tags', $batch) }}" onsubmit="return confirm('Kích hoạt lưu hành {{ $tagCounts['bound'] }} tem đang chờ của lô này? Từ giờ khách hàng quét mã sẽ thấy đầy đủ thông tin sản phẩm.');">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm">Kích hoạt lưu hành ({{ $tagCounts['bound'] }})</button>
        </form>
        @endif
        @if($batch->status->value !== 'recalled')
        <form method="POST" action="{{ route('backend.batches.recall', $batch) }}" onsubmit="return confirm('Đánh dấu lô này là thu hồi? Hành động này sẽ khóa toàn bộ hàng của lô trên kệ.');">
            @csrf
            <button type="submit" class="btn btn-error btn-sm">Thu hồi lô</button>
        </form>
        @endif
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="alert alert-error py-2.5 px-4 mb-5 text-sm">{{ session('error') }}</div>
@endif

@if($tagsTotal > 0)
<div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
    <div class="card-body">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold">Tem truy vết QR</h2>
                <p class="text-xs text-base-content/50 mt-0.5">
                    {{ $tagsTotal }} tem ·
                    Chờ lưu hành: {{ $tagCounts['bound'] ?? 0 }} ·
                    Còn kệ: {{ $tagCounts['in_stock'] ?? 0 }} ·
                    Đã bán: {{ $tagCounts['sold'] ?? 0 }} ·
                    Đã xuất buôn (chờ lưu hành): {{ $tagCounts['transferred'] ?? 0 }} ·
                    Đã xuất buôn (đang lưu hành): {{ $tagCounts['transferred_active'] ?? 0 }} ·
                    Hư hỏng: {{ $tagCounts['damaged'] ?? 0 }} ·
                    Thu hồi: {{ $tagCounts['recalled'] ?? 0 }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('backend.batches.tags.index', $batch) }}" class="btn btn-ghost btn-sm">Xem danh sách tem</a>
                <a href="{{ route('backend.batches.tags.print', $batch) }}" class="btn btn-primary btn-sm">In tem QR (PDF)</a>
            </div>
        </div>
    </div>
</div>
@else
<div class="alert alert-warning py-2.5 px-4 mb-4 text-sm">
    Lô này chưa có tem truy vết QR nào ({{ $batch->initial_qty }} đơn vị chờ dán tem).
</div>
@endif

@can('update', $batch)
<div class="card bg-base-100 shadow-sm border-2 border-primary/30 mb-6">
    <div class="card-body">
        <h2 class="text-base font-semibold mb-1">Kích hoạt Tem Truy vết cho Lô hàng</h2>
        <p class="text-xs text-base-content/50 mb-3">
            Lô cần dán tem cho <strong>{{ $batch->initial_qty }}</strong> đơn vị hàng — đã gắn <strong>{{ $tagsTotal }}</strong>, còn cần <strong>{{ $remainingToTag }}</strong> tem.
            Chọn cuộn tem tiền định danh đã in sẵn (xem "Prefix" và "Số thứ tự in" trên tem) để gắn đúng dải vào lô này. Nếu chỉ cần dán bù vài tem lẻ (tem hỏng, tem rời), dùng
            <a href="{{ route('backend.tag-scan-bind.create') }}" class="link link-primary">Gắn kết rời rạc bằng súng quét</a>.
        </p>

        @if($remainingToTag <= 0)
        <div class="alert alert-success py-2 px-3 text-xs">Lô này đã đủ tem — không cần gắn thêm.</div>
        @elseif($availableRolls->isEmpty())
        <div class="alert alert-warning py-2 px-3 text-xs">
            Không còn cuộn tem tiền định danh nào sẵn sàng. Vào <a href="{{ route('backend.tag-rolls.index') }}" class="link">Kho tem tiền định danh</a> để in thêm.
        </div>
        @else
        <form method="POST" action="{{ route('backend.batches.bind-tags-range', $batch) }}" id="bindForm" class="space-y-3"
              onsubmit="return confirm('Kích hoạt dải tem này cho lô hàng?');">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Cuộn tem <span class="text-error">*</span></span></label>
                    <select name="roll_id" id="rollSelect" required class="select select-bordered select-sm w-full" onchange="onRollChange()">
                        <option value="">— Chọn cuộn tem —</option>
                        @foreach($availableRolls as $availableRoll)
                        <option value="{{ $availableRoll->id }}"
                                data-from="{{ $availableRoll->from_sequence }}"
                                data-to="{{ $availableRoll->to_sequence }}">
                            {{ $availableRoll->prefix ?: '(không prefix)' }} — còn {{ number_format($availableRoll->live_counts['provisioned']) }} tem
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Từ số <span class="text-error">*</span></span></label>
                    <input type="number" name="start_sequence" id="startSeq" min="1" required class="input input-bordered input-sm w-full font-mono" oninput="onStartChange()">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Đến số <span class="text-error">*</span></span></label>
                    <input type="number" name="end_sequence" id="endSeq" min="1" required class="input input-bordered input-sm w-full font-mono" oninput="onEndChange()">
                </div>
            </div>
            <p id="liveMsg" class="text-sm font-medium"></p>
            <button type="submit" id="submitBindBtn" class="btn btn-primary btn-sm" disabled>Kích hoạt</button>
        </form>

        <script>
        (function () {
            var qtyNeeded  = {{ $remainingToTag }};
            var rollSelect = document.getElementById('rollSelect');
            var startInput = document.getElementById('startSeq');
            var endInput   = document.getElementById('endSeq');
            var msg        = document.getElementById('liveMsg');
            var btn        = document.getElementById('submitBindBtn');

            window.onRollChange = function () {
                var opt = rollSelect.options[rollSelect.selectedIndex];
                if (opt.value) {
                    var from = parseInt(opt.dataset.from, 10);
                    var to   = parseInt(opt.dataset.to, 10);
                    startInput.value = from;
                    endInput.value   = Math.min(from + qtyNeeded - 1, to);
                }
                recalc();
            };

            window.onStartChange = function () {
                var start = parseInt(startInput.value, 10) || 0;
                var opt   = rollSelect.options[rollSelect.selectedIndex];
                if (start > 0 && opt.value) {
                    var to = parseInt(opt.dataset.to, 10);
                    endInput.value = Math.min(start + qtyNeeded - 1, to);
                }
                recalc();
            };

            window.onEndChange = function () { recalc(); };

            function recalc() {
                var start = parseInt(startInput.value, 10) || 0;
                var end   = parseInt(endInput.value, 10) || 0;
                var opt   = rollSelect.options[rollSelect.selectedIndex];
                var count = (start > 0 && end >= start) ? (end - start + 1) : 0;
                var valid = count > 0 && !!opt.value;
                var text  = count > 0 ? ('Sẽ kích hoạt ' + count + ' tem cho lô này.') : 'Nhập dải số hợp lệ.';

                if (opt.value) {
                    var rollFrom = parseInt(opt.dataset.from, 10);
                    var rollTo   = parseInt(opt.dataset.to, 10);
                    if (start > 0 && (start < rollFrom || end > rollTo)) {
                        valid = false;
                        text  = 'Dải số nằm ngoài phạm vi cuộn đã chọn (' + rollFrom + '–' + rollTo + ').';
                    }
                }

                if (valid && count > qtyNeeded) {
                    valid = false;
                    text  = 'Sẽ kích hoạt ' + count + ' tem — vượt quá số lượng cần (' + qtyNeeded + '). Vui lòng thu hẹp dải số.';
                }

                msg.textContent = text;
                msg.className = 'text-sm font-medium ' + (valid ? 'text-success' : 'text-error');
                btn.disabled = !valid;
            }
        })();
        </script>
        @endif
    </div>
</div>
@endcan

@php($canUpdateBatch = auth()->user()->can('update', $batch))
<div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
    <div class="card-body">
        <h2 class="text-base font-semibold mb-3">Các dải tem đã gán cho lô này</h2>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Cuộn tem (Prefix)</th>
                        <th>Dải số đã gán</th>
                        <th>Số lượng</th>
                        <th>Trạng thái</th>
                        @can('update', $batch)
                        <th></th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($allocations as $allocation)
                    <tr>
                        <td class="font-mono">{{ $allocation['prefix'] ?? '— (tem cũ)' }}</td>
                        <td class="font-mono">
                            @if($allocation['from'] !== null)
                                {{ $allocation['from'] }}{{ $allocation['to'] > $allocation['from'] ? '–' . $allocation['to'] : '' }}
                            @else
                                <span class="text-base-content/40">Không seri hóa</span>
                            @endif
                        </td>
                        <td>{{ number_format($allocation['count']) }}</td>
                        <td>
                            @php($status = $allocation['status'])
                            @if(in_array($status->value, ['damaged', 'recalled']))
                                <span class="badge {{ $status->badgeClass() }} badge-sm">{{ $status->label() }}</span>
                            @elseif($status->isMarketReleased())
                                <span class="badge badge-success badge-sm">Đã lưu hành</span>
                            @else
                                <span class="badge badge-warning badge-sm">Chờ lưu hành</span>
                            @endif
                        </td>
                        @can('update', $batch)
                        <td class="text-right">
                            @if($allocation['from'] !== null && in_array($status->value, ['bound', 'in_stock']))
                            <form method="POST" action="{{ route('backend.batches.unbind-tag-range', $batch) }}" class="inline"
                                  onsubmit="return confirm('Gỡ dải tem {{ $allocation['from'] }}–{{ $allocation['to'] }} khỏi lô này? Tem sẽ quay về kho tiền định danh.');">
                                @csrf
                                <input type="hidden" name="from_sequence" value="{{ $allocation['from'] }}">
                                <input type="hidden" name="to_sequence" value="{{ $allocation['to'] }}">
                                <button type="submit" class="btn btn-ghost btn-xs text-error">Gỡ dải tem</button>
                            </form>
                            @endif
                        </td>
                        @endcan
                    </tr>
                    @empty
                    <tr><td colspan="{{ $canUpdateBatch ? 5 : 4 }}" class="text-center text-sm text-base-content/50 py-4">Lô này chưa có dải tem nào được gán.</td></tr>
                    @endforelse

                    @if($remainingToTag > 0)
                    <tr class="bg-warning/20">
                        <td colspan="2" class="font-medium">Chưa xác định — hãy dùng form phía trên để gán tiếp</td>
                        <td class="font-medium">{{ number_format($remainingToTag) }}</td>
                        <td colspan="{{ $canUpdateBatch ? 2 : 1 }}" class="font-medium">Đang chờ gán tem: {{ number_format($remainingToTag) }} đơn vị hàng</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6 items-start">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-4">Chứng từ của lô (COA / Kiểm nghiệm)</h2>

            @if($batch->documents->isEmpty())
            <p class="text-sm text-base-content/50">Chưa có chứng từ nào được đính kèm.</p>
            @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Loại chứng từ</th>
                            <th>Số hiệu</th>
                            <th>File</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batch->documents as $document)
                        <tr>
                            <td>{{ $document->document_code->label() }}</td>
                            <td class="font-mono">{{ $document->document_number }}</td>
                            <td>
                                @if($document->file_url)
                                <a href="{{ $document->file_url }}" target="_blank" class="link link-primary text-xs">Xem file</a>
                                @else
                                <span class="text-xs text-base-content/40">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @can('update', $batch)
                                <form method="POST" action="{{ route('backend.batches.documents.destroy', [$batch, $document]) }}"
                                      onsubmit="return confirm('Xóa chứng từ này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs text-error">Xóa</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Thông tin lô</h2>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-base-content/50 text-xs">Mã lô nhà sản xuất</dt><dd class="font-mono">{{ $batch->mfg_batch_number ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Ngày sản xuất</dt><dd>{{ $batch->mfg_date?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Hạn sử dụng</dt><dd>{{ $batch->exp_date->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Số lượng ban đầu</dt><dd>{{ $batch->initial_qty }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Tồn kho hiện tại</dt><dd>{{ $batch->current_qty }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Phiếu nhập kho</dt><dd><a href="{{ route('backend.inbound-receipts.show', $batch->inboundReceipt) }}" class="link">{{ $batch->inboundReceipt->receipt_number }}</a></dd></div>
                </dl>
            </div>
        </div>

        @can('update', $batch)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Thêm chứng từ lô</h2>

                @if($errors->any())
                <div class="alert alert-error py-2 px-3 mb-3 text-xs">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('backend.batches.documents.store', $batch) }}" class="space-y-3">
                    @csrf

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại chứng từ</span></label>
                        <select name="document_code" class="select select-bordered select-sm w-full">
                            @foreach(\Modules\Warehouse\Enums\BatchDocumentType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('document_code') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số hiệu</span></label>
                        <input type="text" name="document_number" value="{{ old('document_number') }}" class="input input-bordered input-sm w-full">
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Đường dẫn file scan</span></label>
                        <input type="url" name="file_url" value="{{ old('file_url') }}" class="input input-bordered input-sm w-full">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-full">Thêm chứng từ</button>
                </form>
            </div>
        </div>
        @endcan

    </div>
</div>
@endsection
