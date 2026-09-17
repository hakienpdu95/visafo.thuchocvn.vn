@extends('layouts.backend')
@section('title', $package->name)

@section('content')

@php $isDraft = $package->status->value === 'draft'; @endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $package->name }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $package->customer->name }} — Phiên bản V{{ $package->version }}</p>
    </div>
    <div class="flex gap-2">
        @if($isDraft)
        <a href="{{ route('backend.sales-packages.edit', $package) }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Sửa gói
        </a>
        @endif
        @if($package->items->isEmpty())
        <button type="button" class="btn btn-primary btn-sm gap-1.5 btn-disabled" disabled
                title="Gói chưa có tài liệu nào — không thể xuất">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
            Xuất gói (ZIP)
        </button>
        @else
        <a href="{{ route('backend.sales-packages.export', $package) }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
            Xuất gói (ZIP)
        </a>
        @endif
        <a href="{{ route('backend.sales-packages.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error py-2.5 px-4 mb-5 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="lg:col-span-2 rounded-2xl bg-green-800 text-white p-6 flex items-center gap-6">
        <div class="text-4xl font-bold">{{ $package->readiness_score }}<span class="text-lg text-white/60">/100</span></div>
        <div>
            <div class="font-semibold">Điểm sẵn sàng tại thời điểm chốt gói</div>
            <p class="text-sm text-white/70 mt-1">Ghi nhận ngày {{ $package->created_at->format('d/m/Y H:i') }} bởi {{ $package->creator->name ?? '—' }}</p>
        </div>
    </div>
    <div class="rounded-2xl bg-base-100 border border-base-200 shadow-sm p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-base-content/60">Trạng thái</span>
            <span class="badge badge-sm badge-soft {{ $package->status->badgeClass() }}">{{ $package->status->label() }}</span>
        </div>
        @if(count($package->status->transitions()) > 0)
        <div class="flex flex-wrap items-center gap-1.5 mb-2">
            @foreach($package->status->transitions() as $nextStatus)
            <form method="POST" action="{{ route('backend.sales-packages.status', $package) }}"
                  @if($nextStatus->value === 'draft') onsubmit="return confirm('Mở lại gói về trạng thái Nháp để chỉnh sửa?');" @endif>
                @csrf
                <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                <button type="submit" class="btn btn-xs {{ $nextStatus->value === 'draft' ? 'btn-ghost' : 'btn-outline' }}">
                    {{ $nextStatus->value === 'draft' ? 'Mở lại để sửa' : 'Chuyển sang: ' . $nextStatus->label() }}
                </button>
            </form>
            @endforeach
        </div>
        @endif
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-base-content/60">Số tài liệu</span>
            <span class="font-semibold">{{ $package->items->count() }}</span>
        </div>
        @if($package->expected_deadline)
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-base-content/60">Hạn nộp dự kiến</span>
            <span class="font-semibold">{{ $package->expected_deadline->format('d/m/Y') }}</span>
        </div>
        @endif
        @if($package->notes)
        <p class="text-xs text-base-content/50 mt-2">{{ $package->notes }}</p>
        @endif
    </div>
</div>

@foreach($package->items->groupBy(fn ($item) => $item->groupLabel()) as $groupLabel => $items)
@php $groupIsCustom = (bool) $items->first()->is_custom; @endphp
<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body">
        <h2 class="card-title text-base mb-2">{{ $groupLabel }}</h2>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Tên tài liệu</th>
                        <th>Số hiệu</th>
                        <th>Ngày hết hạn</th>
                        <th>Tình trạng</th>
                        <th>File đính kèm</th>
                        @if($groupIsCustom)
                        <th class="text-right">Thao tác</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>
                            {{ $item->displayName() }}
                            @if($item->is_custom)
                            <span class="badge badge-ghost badge-xs ml-1">Bổ sung</span>
                            @endif
                        </td>
                        <td>{{ $item->is_custom ? '—' : ($item->document->document_number ?? '—') }}</td>
                        <td>{{ $item->is_custom ? '—' : (optional($item->document->expiration_date)->format('d/m/Y') ?? '—') }}</td>
                        <td>
                            <span class="badge badge-sm badge-soft {{ $item->is_valid ? 'badge-success' : 'badge-error' }}">
                                {{ $item->is_valid ? 'Còn hiệu lực' : 'Cần lưu ý' }}
                            </span>
                        </td>
                        <td>
                            @if($item->fileUrl())
                            <a href="{{ $item->fileUrl() }}" target="_blank" rel="noopener" class="btn btn-ghost btn-xs gap-1 text-info">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Xem tệp
                            </a>
                            @else
                            <span class="text-xs text-base-content/30">Chưa có tệp</span>
                            @endif
                        </td>
                        @if($groupIsCustom)
                        <td class="text-right">
                            @if($isDraft)
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa"
                                        onclick="window.salesPackageEditItem('{{ route('backend.sales-packages.items.update', [$package, $item]) }}', {{ Js::from($item->custom_name) }})">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form method="POST" action="{{ route('backend.sales-packages.items.destroy', [$package, $item]) }}"
                                      onsubmit="return confirm('Xóa tài liệu bổ sung này khỏi gói?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

@if($isDraft)
<dialog id="editCustomItemModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg">Sửa tài liệu bổ sung</h3>
        <p class="text-sm text-base-content/50 mb-4">Đổi tên hoặc thay thế file đính kèm</p>

        <form method="POST" id="editCustomItemForm" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Tên tài liệu <span class="text-error">*</span></span>
                </label>
                <input type="text" name="custom_name" id="editCustomItemName" required
                       class="input input-bordered input-sm w-full">
            </div>

            <div class="form-control mt-3">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Thay thế file</span>
                    <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                </label>
                <input type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                       class="file-input file-input-bordered file-input-sm w-full">
            </div>

            <div class="modal-action mt-4">
                <button type="button" class="btn btn-ghost btn-sm" onclick="editCustomItemModal.close()">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endif

@endsection

@push('scripts')
    @vite(['Modules/SalesPackage/resources/assets/js/salespackage.js'], 'build/backend')
@endpush
