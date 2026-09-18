@extends('layouts.backend')
@section('title', $goodsReceipt->misa_ref_id)

@section('content')
@php($batchesByProduct = $goodsReceipt->batches->keyBy('product_id'))
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

<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6 items-start">

    <div class="space-y-6">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-0 overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên hàng</th>
                            <th>Mã hàng</th>
                            <th>ĐVT</th>
                            <th class="text-right">SL nhập</th>
                            <th>Mã lô</th>
                            <th>NSX</th>
                            <th>HSD</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($goodsReceipt->items as $item)
                        @php($batch = $batchesByProduct->get($item->product_id))
                        <tr x-data="{ editing: false, mfg: '{{ $batch?->mfg_date?->format('Y-m-d') }}', exp: '{{ $batch?->exp_date?->format('Y-m-d') }}' }">
                            <td>{{ $item->line_no }}</td>
                            <td>{{ $item->product_name_raw }}</td>
                            <td class="font-mono text-xs">{{ $item->product?->sku }}</td>
                            <td>{{ $item->unit_raw }}</td>
                            <td class="text-right font-mono">{{ number_format((float) $item->quantity, 3) }}</td>
                            <td class="font-mono text-xs">{{ $batch?->batch_code }}</td>

                            <template x-if="!editing">
                                <td x-text="mfg || '—'"></td>
                            </template>
                            <template x-if="editing">
                                <td><input type="date" x-model="mfg" class="input input-xs input-bordered w-32"></td>
                            </template>

                            <template x-if="!editing">
                                <td x-text="exp || '—'"></td>
                            </template>
                            <template x-if="editing">
                                <td><input type="date" x-model="exp" class="input input-xs input-bordered w-32"></td>
                            </template>

                            <td>
                                @can('update', $batch)
                                <template x-if="!editing">
                                    <button class="btn btn-ghost btn-xs" @click="editing = true">Sửa</button>
                                </template>
                                <template x-if="editing">
                                    <form method="POST" action="{{ route('backend.product-batches.update', $batch) }}" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="mfg_date" :value="mfg">
                                        <input type="hidden" name="exp_date" :value="exp">
                                        <button type="submit" class="btn btn-primary btn-xs">Lưu</button>
                                    </form>
                                </template>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Chi tiết phiếu</p>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Ngày nhập</dt>
                        <dd>{{ $goodsReceipt->receipt_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Nhà cung cấp (Excel)</dt>
                        <dd>{{ $goodsReceipt->supplier_name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Nhà cung cấp (khớp hệ thống)</dt>
                        <dd>{{ $goodsReceipt->vendor?->name ?? '— (chưa khớp)' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">File nguồn</dt>
                        <dd class="font-mono text-xs">{{ $goodsReceipt->source_file_name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Người import</dt>
                        <dd>{{ $goodsReceipt->importedBy?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Nhập lúc</dt>
                        <dd>{{ $goodsReceipt->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

</div>
@endsection
