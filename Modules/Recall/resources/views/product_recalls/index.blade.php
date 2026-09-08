@extends('layouts.backend')
@section('title', 'Chiến dịch thu hồi')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chiến dịch thu hồi sản phẩm</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Khởi tạo và theo dõi thu hồi theo SKU hoặc theo lô cụ thể</p>
    </div>
    @can('create', \Modules\Recall\Models\ProductRecall::class)
    <a href="{{ route('backend.product-recalls.create') }}" class="btn btn-error btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
        </svg>
        Khởi tạo thu hồi
    </a>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body py-3 px-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs">Trạng thái</span></label>
                <select name="status" class="select select-bordered select-sm">
                    <option value="">Tất cả</option>
                    @foreach($statuses as $status)
                    <option value="{{ $status['value'] }}" @selected(request('status') === $status['value'])>{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline">Lọc</button>
        </form>
    </div>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Phạm vi</th>
                    <th>Mức độ</th>
                    <th>Ngày khởi tạo</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recalls as $recall)
                <tr>
                    <td>
                        <a href="{{ route('backend.product-recalls.show', $recall) }}" class="link link-hover font-medium">{{ $recall->product->name }}</a>
                    </td>
                    <td>{{ $recall->batch ? 'Lô ' . $recall->batch->internal_batch_code : 'Toàn bộ SKU' }}</td>
                    <td>{{ $recall->severity?->label() ?? '—' }}</td>
                    <td>{{ $recall->initiated_at->format('d/m/Y') }}</td>
                    <td><span class="badge {{ $recall->status->badgeClass() }} badge-sm">{{ $recall->status->label() }}</span></td>
                    <td class="text-right">
                        <a href="{{ route('backend.product-recalls.show', $recall) }}" class="btn btn-ghost btn-xs">Xem</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-sm text-base-content/50 py-6">Chưa có chiến dịch thu hồi nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($recalls->hasPages())
    <div class="card-body py-3 px-4 border-t border-base-200">
        {{ $recalls->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
