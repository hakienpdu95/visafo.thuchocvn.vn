@extends('layouts.backend')
@section('title', $vendor->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            {{ $vendor->name }}
            <span class="badge {{ $vendor->status->badgeClass() }} badge-sm">{{ $vendor->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5 font-mono">
            {{ $vendor->vendor_code ?? '—' }} · MST {{ $vendor->tax_code }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.vendors.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        @can('update', $vendor)
        <a href="{{ route('backend.vendors.edit', $vendor) }}" class="btn btn-primary btn-sm">Chỉnh sửa</a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6 items-start">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-4">Hồ sơ pháp lý</h2>

            @if($vendor->documents->isEmpty())
            <p class="text-sm text-base-content/50">Chưa có hồ sơ nào được ghi nhận.</p>
            @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Loại</th>
                            <th>Số hiệu</th>
                            <th>Ngày cấp</th>
                            <th>Hạn dùng</th>
                            <th>Trạng thái</th>
                            <th>File</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vendor->documents as $document)
                        <tr>
                            <td>{{ $document->documentType->name }}</td>
                            <td class="font-mono">{{ $document->document_number ?? '—' }}</td>
                            <td>{{ $document->issue_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                {{ $document->expiration_date?->format('d/m/Y') ?? '—' }}
                                @if($document->isExpired())
                                <span class="badge badge-error badge-xs ml-1">Hết hạn</span>
                                @elseif($document->isExpiringWithinDays(30))
                                <span class="badge badge-warning badge-xs ml-1">Sắp hết hạn</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $document->status->badgeClass() }} badge-xs">{{ $document->status->label() }}</span>
                            </td>
                            <td>
                                @if($document->getFirstMediaUrl('attachments_private'))
                                <a href="{{ $document->getFirstMediaUrl('attachments_private') }}" target="_blank" class="link link-primary text-xs">Xem file</a>
                                @else
                                <span class="text-xs text-base-content/40">—</span>
                                @endif
                            </td>
                            <td>
                                @can('update', $vendor)
                                <form method="POST" action="{{ route('backend.vendors.documents.destroy', [$vendor, $document]) }}"
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

    <div class="space-y-6">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Thông tin pháp nhân</h2>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-base-content/50 text-xs">Địa chỉ</dt>
                        <dd>
                            {{ $vendor->address ?? '—' }}
                            @if($vendor->ward || $vendor->province)
                                <br>{{ trim(($vendor->ward?->name ?? '') . ', ' . ($vendor->province?->name ?? ''), ', ') }}
                            @endif
                        </dd>
                    </div>
                    <div><dt class="text-base-content/50 text-xs">Điện thoại công ty</dt><dd>{{ $vendor->phone_number ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Email công ty</dt><dd>{{ $vendor->email ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Người đại diện theo pháp luật</h2>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-base-content/50 text-xs">Họ và tên</dt><dd>{{ $vendor->representative_name ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Chức danh</dt><dd>{{ $vendor->representative_title ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Điện thoại</dt><dd>{{ $vendor->representative_phone ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Email</dt><dd>{{ $vendor->representative_email ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Đầu mối liên hệ về công việc</h2>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-base-content/50 text-xs">Họ và tên</dt><dd>{{ $vendor->contact_person_name ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Chức vụ</dt><dd>{{ $vendor->contact_person_title ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Điện thoại</dt><dd>{{ $vendor->contact_person_phone ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Email</dt><dd>{{ $vendor->contact_person_email ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>

        @can('update', $vendor)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Thêm hồ sơ mới</h2>

                @if($errors->any())
                <div class="alert alert-error py-2 px-3 mb-3 text-xs">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('backend.vendors.documents.store', $vendor) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại giấy tờ</span></label>
                        <select name="document_master_type_id" class="select select-bordered select-sm w-full">
                            @foreach($documentTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('document_master_type_id') === $type->id)>{{ $type->name }}</option>
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
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Nơi cấp</span></label>
                        <input type="text" name="issued_by" value="{{ old('issued_by') }}" class="input input-bordered input-sm w-full">
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">File PDF/Scan</span></label>
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="file-input file-input-bordered file-input-sm w-full">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-full">Thêm hồ sơ</button>
                </form>
            </div>
        </div>
        @endcan

    </div>
</div>
@endsection
