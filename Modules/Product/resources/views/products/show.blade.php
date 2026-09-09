@extends('layouts.backend')
@section('title', $product->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            {{ $product->name }}
            <span class="badge {{ $product->status->badgeClass() }} badge-sm">{{ $product->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5 font-mono">
            {{ $product->sku }} · {{ $product->category?->name }} · {{ $product->product_type->label() }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        @can('update', $product)
        <a href="{{ route('backend.products.edit', $product) }}" class="btn btn-primary btn-sm">Chỉnh sửa</a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6 items-start">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-4">Hồ sơ pháp lý</h2>

            @if($product->compliances->isEmpty())
            <p class="text-sm text-base-content/50">Chưa có hồ sơ pháp lý nào được ghi nhận.</p>
            @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Loại giấy tờ</th>
                            <th>Số hiệu</th>
                            <th>Phân loại</th>
                            <th>Ngày cấp</th>
                            <th>Hết hạn</th>
                            <th>Trạng thái</th>
                            <th>File</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->compliances as $compliance)
                        <tr>
                            <td>{{ $compliance->documentType->name }}</td>
                            <td class="font-mono">{{ $compliance->document_number }}</td>
                            <td>{{ $compliance->classification_grade?->label() ?? '—' }}</td>
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
                            <td class="space-x-2">
                                @if($compliance->file_url)
                                <a href="{{ $compliance->file_url }}" target="_blank" class="link link-primary text-xs">File</a>
                                @endif
                                @if($compliance->pif_file_url)
                                <a href="{{ $compliance->pif_file_url }}" target="_blank" class="link link-primary text-xs">PIF</a>
                                @endif
                                @if(!$compliance->file_url && !$compliance->pif_file_url)
                                <span class="text-xs text-base-content/40">—</span>
                                @endif
                            </td>
                            <td>
                                @can('delete', $compliance)
                                <form method="POST" action="{{ route('backend.products.compliances.destroy', [$product, $compliance]) }}"
                                      onsubmit="return confirm('Xóa hồ sơ pháp lý này?');">
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
                <h2 class="text-base font-semibold mb-3">Thông tin sản phẩm</h2>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-base-content/50 text-xs">Mã vạch</dt><dd>{{ $product->barcode ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Đơn vị tính</dt><dd>{{ $product->unit }}</dd></div>
                </dl>
            </div>
        </div>

        @can('create', \Modules\Product\Models\ProductCompliance::class)
        <div id="add-compliance" class="card bg-base-100 shadow-sm border border-base-200 scroll-mt-24">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Thêm hồ sơ pháp lý mới</h2>

                @if($errors->any())
                <div class="alert alert-error py-2 px-3 mb-3 text-xs">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                @if($documentTypes->isEmpty())
                <p class="text-xs text-base-content/50">Chưa có loại giấy tờ nào được cấu hình trong hệ thống. Vào <a href="{{ route('backend.document-master-types.index') }}" class="link">Từ điển giấy tờ pháp lý</a> để thêm.</p>
                @else
                <form method="POST" action="{{ route('backend.products.compliances.store', $product) }}" class="space-y-3">
                    @csrf

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại giấy tờ</span></label>
                        <select name="document_type_id" class="select select-bordered select-sm w-full">
                            @foreach($documentTypes as $categoryLabel => $types)
                            <optgroup label="{{ $categoryLabel }}">
                                @foreach($types as $type)
                                <option value="{{ $type->id }}" @selected(old('document_type_id') === $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số hiệu</span></label>
                        <input type="text" name="document_number" value="{{ old('document_number') }}" class="input input-bordered input-sm w-full">
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Phân loại (chỉ TBYT)</span></label>
                        <select name="classification_grade" class="select select-bordered select-sm w-full">
                            <option value="">— Không áp dụng —</option>
                            @foreach(\Modules\Product\Enums\ClassificationGrade::cases() as $grade)
                            <option value="{{ $grade->value }}" @selected(old('classification_grade') === $grade->value)>{{ $grade->label() }}</option>
                            @endforeach
                        </select>
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

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Đường dẫn file PIF (chỉ mỹ phẩm)</span></label>
                        <input type="url" name="pif_file_url" value="{{ old('pif_file_url') }}" class="input input-bordered input-sm w-full">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-full">Thêm hồ sơ</button>
                </form>
                @endif
            </div>
        </div>
        @endcan

    </div>
</div>
@endsection
