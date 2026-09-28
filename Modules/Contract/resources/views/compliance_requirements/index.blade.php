@extends('layouts.backend')
@section('title', 'Danh mục yêu cầu bắt buộc với NCC')

@section('content')
@php
    $canCreate = auth()->user()->can('create', \Modules\Contract\Models\VendorComplianceRequirement::class);
    $canUpdate = auth()->user()->can('update', \Modules\Contract\Models\VendorComplianceRequirement::class);
    $canDelete = auth()->user()->can('delete', \Modules\Contract\Models\VendorComplianceRequirement::class);
@endphp
<div x-data="complianceRequirementPage({{ Js::from([
    'options'   => $options,
    'storeUrl'  => route('backend.vendor-compliance-requirements.store'),
    'updateUrl' => route('backend.vendor-compliance-requirements.update', '__KEY__'),
    'old'       => $errors->any() ? [
        'group'        => old('_group'),
        'kind'         => old('kind', 'document'),
        'target_ids'   => old('target_ids', []),
        'label'        => old('label', ''),
        'source_group' => old('source_group', ''),
        'is_mandatory' => (bool) old('is_mandatory'),
        'warning_days' => old('warning_days', 30),
        'legal_basis'  => old('legal_basis', ''),
    ] : null,
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Danh mục yêu cầu bắt buộc với NCC</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Mốc đối chiếu để tìm hồ sơ / hợp đồng còn thiếu trên màn Tuân thủ theo NCC</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.contracts.index') }}#compliance" class="btn btn-ghost btn-sm">Xem tuân thủ theo NCC</a>
            @if($canCreate)
            <button type="button" class="btn btn-primary btn-sm gap-1.5" @click="openCreate()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm yêu cầu
            </button>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
    @endif

    <div class="alert alert-info alert-soft py-3 px-4 mb-5 text-sm items-start">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <ul class="list-disc list-inside space-y-0.5">
            <li>Mỗi NCC được đối chiếu với các yêu cầu <strong>áp dụng cho mọi NCC</strong> cộng với các yêu cầu riêng của <strong>nhóm nguồn</strong> của NCC đó.</li>
            <li>Một yêu cầu có thể chấp nhận nhiều loại hồ sơ / hợp đồng — NCC chỉ cần có <strong>một trong số đó</strong> là đạt.</li>
            <li>Chỉ hồ sơ đã duyệt (active) và hợp đồng đầu vào chưa chấm dứt mới được tính. Yêu cầu "khuyến nghị" được hiển thị nhưng không tính vào số còn thiếu.</li>
        </ul>
    </div>

    <div class="space-y-5">
        @foreach($scopes as $scope)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h2 class="text-base font-semibold flex items-center gap-2">
                        {{ $scope['text'] }}
                        <span class="badge badge-ghost badge-sm">{{ $scope['vendors'] }} NCC đang hợp tác</span>
                    </h2>
                    @if($canCreate)
                    <button type="button" class="btn btn-ghost btn-xs" @click="openCreate(@js($scope['value']))">+ Thêm yêu cầu cho nhóm này</button>
                    @endif
                </div>

                @if(empty($scope['groups']))
                <p class="text-sm text-base-content/40 py-2">
                    {{ $scope['value'] === '' ? 'Chưa có yêu cầu chung nào.' : 'Chưa có yêu cầu riêng — nhóm này chỉ áp dụng các yêu cầu chung.' }}
                </p>
                @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-xs uppercase text-base-content/40">
                                <th>Yêu cầu</th>
                                <th>Loại</th>
                                <th>Chấp nhận (một trong)</th>
                                <th class="text-center">Cảnh báo trước</th>
                                <th class="text-center">Mức độ</th>
                                <th class="text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scope['groups'] as $group)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $group['label'] }}</p>
                                    @if($group['legal_basis'])
                                    <p class="text-xs text-base-content/40 mt-0.5">{{ $group['legal_basis'] }}</p>
                                    @endif
                                </td>
                                <td><span class="badge badge-sm badge-soft {{ $group['kind'] === 'contract' ? 'badge-info' : 'badge-primary' }} whitespace-nowrap">{{ $group['kind_label'] }}</span></td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($group['targets'] as $target)
                                        <span class="badge badge-ghost badge-sm">{{ $target }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-center text-sm tabular-nums whitespace-nowrap">{{ $group['warning_days'] }} ngày</td>
                                <td class="text-center">
                                    @if($group['is_mandatory'])
                                    <span class="badge badge-error badge-soft badge-sm">Bắt buộc</span>
                                    @else
                                    <span class="badge badge-ghost badge-sm">Khuyến nghị</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    @if($canUpdate)
                                    <button type="button" class="btn btn-ghost btn-xs" @click="openEdit(@js($group))">Sửa</button>
                                    @endif
                                    @if($canDelete)
                                    <form method="POST" action="{{ route('backend.vendor-compliance-requirements.destroy', $group['key']) }}" class="inline"
                                          onsubmit="return confirm(@js('Xóa yêu cầu "' . $group['label'] . '"?'));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-xs text-error">Xóa</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    @if($canCreate || $canUpdate)
    <dialog x-ref="modal" class="modal">
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-lg mb-4" x-text="form.group ? 'Sửa yêu cầu' : 'Thêm yêu cầu'"></h3>

            @if($errors->any())
            <div class="alert alert-error alert-soft py-2 px-3 mb-4 text-xs">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form method="POST" :action="form.group ? updateUrl.replace('__KEY__', form.group) : storeUrl" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" :value="form.group ? 'PUT' : 'POST'">
                <input type="hidden" name="_group" :value="form.group ?? ''">

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Loại yêu cầu <span class="text-error">*</span></span></label>
                    <div class="flex gap-6">
                        @foreach($kinds as $kind)
                        <label class="label cursor-pointer justify-start gap-2 py-0">
                            <input type="radio" name="kind" value="{{ $kind['value'] }}" x-model="form.kind" @change="form.target_ids = []"
                                   class="radio radio-sm radio-primary">
                            <span class="label-text">{{ $kind['text'] }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text text-xs font-medium">Chấp nhận loại <span class="text-error">*</span></span>
                        <span class="label-text-alt text-xs text-base-content/40">Chọn nhiều = chỉ cần có một</span>
                    </label>
                    <div class="border border-base-300 rounded-box max-h-52 overflow-y-auto divide-y divide-base-200 @error('target_ids') border-error @enderror @error('target_ids.*') border-error @enderror">
                        <template x-for="opt in currentOptions" :key="opt.value">
                            <label class="flex items-center gap-2 px-3 py-2 cursor-pointer hover:bg-base-200/50">
                                <input type="checkbox" name="target_ids[]" :value="opt.value" x-model="form.target_ids"
                                       @change="suggestLabel()" class="checkbox checkbox-sm checkbox-primary">
                                <span class="text-sm" x-text="opt.text"></span>
                            </label>
                        </template>
                        <p x-show="currentOptions.length === 0" class="px-3 py-2 text-sm text-base-content/40">Chưa có loại nào trong từ điển.</p>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Tên yêu cầu <span class="text-error">*</span></span></label>
                    <input type="text" name="label" x-model="form.label" @input="labelTouched = true" maxlength="255"
                           class="input input-bordered input-sm w-full @error('label') input-error @enderror"
                           placeholder="VD: Giấy kiểm dịch thú y hoặc VietGAP">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Áp dụng cho</span></label>
                        <select name="source_group" x-model="form.source_group" class="select select-bordered select-sm w-full">
                            @foreach($scopes as $scope)
                            <option value="{{ $scope['value'] }}">{{ $scope['value'] === '' ? 'Mọi NCC' : $scope['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Cảnh báo trước (ngày) <span class="text-error">*</span></span></label>
                        <input type="number" name="warning_days" x-model.number="form.warning_days" min="1" max="365"
                               class="input input-bordered input-sm w-full @error('warning_days') input-error @enderror">
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Căn cứ pháp lý</span></label>
                    <input type="text" name="legal_basis" x-model="form.legal_basis" maxlength="255"
                           class="input input-bordered input-sm w-full" placeholder="VD: Nghị định 15/2018/NĐ-CP">
                </div>

                <label class="label cursor-pointer justify-start gap-3 py-0">
                    <input type="checkbox" name="is_mandatory" value="1" x-model="form.is_mandatory" class="toggle toggle-sm toggle-error">
                    <span class="label-text text-sm">Bắt buộc <span class="text-base-content/40 text-xs">(tắt = khuyến nghị, không tính vào "Còn thiếu")</span></span>
                </label>

                <div class="modal-action mt-2">
                    <button type="button" class="btn btn-ghost btn-sm" @click="$refs.modal.close()">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm" x-text="form.group ? 'Lưu thay đổi' : 'Thêm yêu cầu'"></button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    @endif

</div>
@endsection

@push('scripts')
    @vite(['Modules/Contract/resources/assets/js/contract.js'], 'build/backend')
@endpush
