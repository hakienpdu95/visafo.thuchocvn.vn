@extends('layouts.backend')
@section('title', 'Nhật ký đồng bộ Sapo — ' . $batch->internal_batch_code)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content font-mono">{{ $batch->internal_batch_code }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Nhật ký đồng bộ Sapo POS · {{ $entries->total() }} tem đã xuất qua đơn hàng Sapo</p>
    </div>
    <a href="{{ route('backend.batches.show', $batch) }}" class="btn btn-ghost btn-sm">Quay lại lô hàng</a>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Serial tem</th>
                    <th>Thời điểm trừ kho</th>
                    <th>Mã đơn Sapo</th>
                    <th>Trạng thái đơn</th>
                    <th>Trạng thái tem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $tag)
                <tr>
                    <td class="font-mono">{{ $tag->gs1_serial ?? $tag->serial_number }}</td>
                    <td class="whitespace-nowrap">{{ $tag->sold_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                    <td class="font-mono">{{ $tag->externalOrder?->external_order_code ?? '—' }}</td>
                    <td>{{ $tag->externalOrder?->status ?? '—' }}</td>
                    <td><span class="badge {{ $tag->status->badgeClass() }} badge-xs">{{ $tag->status->label() }}</span></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-sm text-base-content/50 py-6">Lô này chưa có tem nào được xuất bán qua Sapo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
    <div class="card-body py-3 px-4 border-t border-base-200">
        {{ $entries->links() }}
    </div>
    @endif
</div>
@endsection
