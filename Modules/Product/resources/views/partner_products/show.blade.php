@extends('layouts.backend')
@section('title', $partnerProduct->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            {{ $partnerProduct->name }}
            <span class="badge {{ $partnerProduct->status->badgeClass() }} badge-sm">{{ $partnerProduct->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5 font-mono">
            {{ $partnerProduct->vendor_sku ?? '—' }} · NCC: {{ $partnerProduct->vendor?->name }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.partner-products.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        @can('update', $partnerProduct)
        <a href="{{ route('backend.partner-products.edit', $partnerProduct) }}" class="btn btn-primary btn-sm">Chỉnh sửa</a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6 items-start">

    <div class="space-y-6">

        {{-- ── Ánh xạ Tier 1 / Tier 2 ──────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-4">Truy xuất nguồn gốc</h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-xs text-base-content/40 mb-0.5">Nhà cung cấp trực tiếp (Tier 1)</dt>
                        <dd class="font-medium">{{ $partnerProduct->vendor?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/40 mb-0.5">Ánh xạ danh mục chuẩn Visafo</dt>
                        <dd class="font-medium">
                            {{ $partnerProduct->product?->name ?? '—' }}
                            @if($partnerProduct->product)
                            <span class="text-xs text-base-content/40 font-mono">({{ $partnerProduct->product->sku }})</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/40 mb-0.5">Nhà sản xuất / Nguồn gốc (Tier 2)</dt>
                        <dd class="font-medium">{{ $partnerProduct->manufacturer_name ?? '— (mua trực tiếp, không qua trung gian)' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/40 mb-0.5">Địa chỉ / Vùng trồng / Lò mổ gốc</dt>
                        <dd class="font-medium">{{ $partnerProduct->origin_address ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- ── Rule Engine: Hồ sơ chất lượng bắt buộc ──────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-1">Hồ sơ chất lượng</h2>
                <p class="text-xs text-base-content/40 mb-4">Tự động xác định theo nhóm hàng của sản phẩm đã ánh xạ (ProductComplianceRuleEngine)</p>

                @if(empty($complianceResults))
                <p class="text-sm text-base-content/50">
                    @if(!$partnerProduct->product)
                    Chưa ánh xạ sản phẩm chuẩn — không thể xác định giấy tờ bắt buộc.
                    @else
                    Nhóm hàng của sản phẩm này chưa có quy tắc giấy tờ bắt buộc trong hệ thống.
                    @endif
                </p>
                @else
                <div class="space-y-2.5">
                    @foreach($complianceResults as $result)
                    <div class="flex items-start gap-3 p-3 rounded-lg border {{ $result->satisfied ? 'border-success/20 bg-success/5' : 'border-error/20 bg-error/5' }}">
                        @if($result->satisfied)
                        <svg class="w-5 h-5 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        @else
                        <svg class="w-5 h-5 text-error shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium">{{ $result->requirement->label }}</p>
                            @if($result->requirement->legalBasis)
                            <p class="text-xs text-base-content/40 mt-0.5">Căn cứ: {{ $result->requirement->legalBasis }}</p>
                            @endif
                            @if($result->satisfied && $result->matchedCompliance)
                            <p class="text-xs text-success mt-1">
                                Đã cấp — {{ $result->matchedCompliance->documentType->name }}
                                (số {{ $result->matchedCompliance->document_number }}@if($result->matchedCompliance->expiration_date), hạn {{ $result->matchedCompliance->expiration_date->format('d/m/Y') }}@endif)
                            </p>
                            @else
                            <p class="text-xs text-error mt-1">Đang thiếu — cần đòi NCC bổ sung</p>
                            @endif
                        </div>
                        <span class="badge badge-sm {{ $result->satisfied ? 'badge-success' : 'badge-error' }} shrink-0">
                            {{ $result->satisfied ? 'Đã cấp' : 'Đang thiếu' }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ── Toàn bộ hồ sơ đã ghi nhận ────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-4">Toàn bộ hồ sơ đã ghi nhận</h2>

                @if($partnerProduct->compliances->isEmpty())
                <p class="text-sm text-base-content/50">Chưa có hồ sơ nào được ghi nhận.</p>
                @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Loại giấy tờ</th>
                                <th>Số hiệu</th>
                                <th>Ngày cấp</th>
                                <th>Hết hạn</th>
                                <th>Trạng thái</th>
                                <th>File</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($partnerProduct->compliances as $compliance)
                            <tr>
                                <td>{{ $compliance->documentType->name }}</td>
                                <td class="font-mono">{{ $compliance->document_number }}</td>
                                <td>{{ $compliance->issue_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    {{ $compliance->expiration_date?->format('d/m/Y') ?? '—' }}
                                    @if($compliance->isExpired())
                                    <span class="badge badge-error badge-xs ml-1">Hết hạn</span>
                                    @elseif($compliance->isExpiringWithinDays(30))
                                    <span class="badge badge-warning badge-xs ml-1">Sắp hết hạn</span>
                                    @endif
                                </td>
                                <td><span class="badge {{ $compliance->status->badgeClass() }} badge-xs">{{ $compliance->status->label() }}</span></td>
                                <td>
                                    @if($compliance->file_url)
                                    <a href="{{ $compliance->file_url }}" target="_blank" class="link link-primary text-xs">File</a>
                                    @else
                                    <span class="text-xs text-base-content/40">—</span>
                                    @endif
                                </td>
                                <td>
                                    @can('delete', $compliance)
                                    <form method="POST" action="{{ route('backend.partner-products.compliances.destroy', [$partnerProduct, $compliance]) }}"
                                          onsubmit="return confirm('Xóa hồ sơ này?');">
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

    </div>

    <div class="space-y-6">

        @can('create', \Modules\Product\Models\PartnerProductCompliance::class)
        <div id="add-compliance" class="card bg-base-100 shadow-sm border border-base-200 scroll-mt-24">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Thêm hồ sơ chất lượng mới</h2>
                <p class="text-xs text-base-content/40 mb-3">Giấy tờ của nhà sản xuất gốc (VD: ISO, OCOP, kiểm dịch của {{ $partnerProduct->manufacturer_name ?? 'nhà sản xuất' }})</p>

                <form method="POST" action="{{ route('backend.partner-products.compliances.store', $partnerProduct) }}" class="space-y-3">
                    @csrf

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại giấy tờ</span></label>
                        <select name="document_type_id" class="select select-bordered select-sm w-full">
                            @foreach(\Modules\Product\Models\DocumentMasterType::orderBy('name')->get() as $type)
                            <option value="{{ $type->id }}" @selected(old('document_type_id') === $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số hiệu</span></label>
                        <input type="text" name="document_number" value="{{ old('document_number') }}" class="input input-bordered input-sm w-full">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày cấp</span></label>
                            <input type="date" name="issue_date" value="{{ old('issue_date') }}" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày hết hạn</span></label>
                            <input type="date" name="expiration_date" value="{{ old('expiration_date') }}" class="input input-bordered input-sm w-full">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Đường dẫn file scan</span></label>
                        <input type="url" name="file_url" value="{{ old('file_url') }}" class="input input-bordered input-sm w-full">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-full">Thêm hồ sơ</button>
                </form>
            </div>
        </div>
        @endcan

    </div>
</div>
@endsection
