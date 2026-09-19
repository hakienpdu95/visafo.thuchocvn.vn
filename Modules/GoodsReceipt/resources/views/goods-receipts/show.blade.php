@extends('layouts.backend')
@section('title', $goodsReceipt->misa_ref_id)

@section('content')
@php
    $batchesByProduct = $goodsReceipt->batches->keyBy('product_id');
    $itemRows = $goodsReceipt->items->map(function ($item) use ($batchesByProduct) {
        $batch = $batchesByProduct->get($item->product_id);
        $canEdit = $batch && auth()->user()->can('update', $batch);

        return [
            'line_no'    => $item->line_no,
            'name'       => $item->product_name_raw,
            'sku'        => $item->product?->sku,
            'unit'       => $item->unit_raw,
            'quantity'   => number_format((float) $item->quantity, 3),
            'batch_code' => $batch?->batch_code,
            'mfg_date'   => $batch?->mfg_date?->format('Y-m-d'),
            'exp_date'   => $batch?->exp_date?->format('Y-m-d'),
            'shelf_life_days' => $item->product?->shelf_life_days,
            'update_url' => $canEdit ? route('backend.product-batches.update', $batch) : null,
        ];
    })->values();
@endphp
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content font-mono">{{ $goodsReceipt->misa_ref_id }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $goodsReceipt->vendor?->name ?? $goodsReceipt->supplier_name ?? '—' }}</p>
    </div>
    <a href="{{ route('backend.goods-receipts.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="flex flex-col gap-6">

    <div class="card w-full bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-4">Chi tiết phiếu</p>

            <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5 text-sm">
                <div class="space-y-4">
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Ngày nhập</dt>
                        <dd class="font-medium">{{ $goodsReceipt->receipt_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Nhập lúc</dt>
                        <dd>{{ $goodsReceipt->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Nhà cung cấp (Excel)</dt>
                        <dd>{{ $goodsReceipt->supplier_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Nhà cung cấp (khớp hệ thống)</dt>
                        <dd>{{ $goodsReceipt->vendor?->name ?? '— (chưa khớp)' }}</dd>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">File nguồn</dt>
                        <dd class="font-mono text-xs break-all">{{ $goodsReceipt->source_file_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Người import</dt>
                        <dd>{{ $goodsReceipt->importedBy?->name ?? '—' }}</dd>
                    </div>
                </div>
            </dl>
        </div>
    </div>

    <div class="card w-full bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-x-auto tabulator-daisy">
            <div id="goods-receipt-items-table" data-rows="{{ json_encode($itemRows, JSON_UNESCAPED_UNICODE) }}"></div>
        </div>
    </div>

</div>

<dialog id="batchDateModal" class="modal">
    <div class="modal-box max-w-md overflow-visible">
        <h3 class="font-bold text-lg">Cập nhật NSX / HSD</h3>
        <p class="text-sm text-base-content/60 mt-1">Lô <strong id="batchDateModalCode" class="font-mono text-base-content"></strong></p>

        <form id="batchDateForm" method="POST" class="mt-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-0.5" for="fp-batch-mfg"><span class="label-text text-xs font-medium">Ngày sản xuất (NSX)</span></label>
                    <input id="fp-batch-mfg" name="mfg_date" type="text" placeholder="dd/mm/yyyy" autocomplete="off"
                           class="input input-sm input-bordered w-full"/>
                </div>
                <div class="form-control">
                    <label class="label py-0.5" for="fp-batch-exp"><span class="label-text text-xs font-medium">Hạn sử dụng (HSD)</span></label>
                    <input id="fp-batch-exp" name="exp_date" type="text" placeholder="dd/mm/yyyy" autocomplete="off"
                           class="input input-sm input-bordered w-full"/>
                    <p id="batchShelfLifeHint" class="mt-1 text-xs text-base-content/40 hidden"></p>
                </div>
            </div>

            <div class="modal-action mt-6">
                <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="batchDateModal.close()">Hủy</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/GoodsReceipt/resources/assets/sass/goodsreceipt.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/flatpickr.js',
        'Modules/GoodsReceipt/resources/assets/js/goodsreceipt.js',
    ], 'build/backend')
@endpush
