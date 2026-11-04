@extends('layouts.backend')
@section('title', 'Người giao hàng HanoiCheck')

@section('content')
<div x-data="hanoiCheckTransporterPage({{ Js::from(['listUrl' => $listUrl, 'storeUrl' => $storeUrl]) }})">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Người giao hàng HanoiCheck</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Đồng bộ lên HanoiCheck (dịch vụ Nhân sự) để gán vào đơn — HanoiCheck bắt buộc biển số phương tiện</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('backend.hanoicheck-orders.index') }}" class="btn btn-ghost btn-sm">← Liên kết đơn</a>
            <button type="button" class="btn btn-primary btn-sm" @click="create()">+ Thêm người giao</button>
        </div>
    </div>

    <div class="card bg-base-100">
        <div class="card-body p-0 overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Mã</th><th>Họ tên</th><th>Điện thoại</th><th>Phương tiện / Biển số</th><th>Hoạt động</th><th>Đồng bộ Sở</th><th></th></tr></thead>
                <tbody>
                    <template x-for="r in rows" :key="r.id">
                        <tr>
                            <td class="font-mono text-xs" x-text="r.code"></td>
                            <td class="font-medium" x-text="r.name"></td>
                            <td x-text="r.phone || '—'"></td>
                            <td><span x-text="r.transport_mean || '—'"></span> · <span class="font-mono" x-text="r.license_plate"></span></td>
                            <td><span class="badge badge-sm" :class="r.is_active ? 'badge-success' : 'badge-ghost'" x-text="r.is_active ? 'Đang làm' : 'Nghỉ'"></span></td>
                            <td class="text-xs">
                                <span class="badge badge-sm" :class="{ 'badge-success': r.sync_status === 'accepted', 'badge-info': r.sync_status === 'fake', 'badge-error': r.sync_status === 'failed', 'badge-ghost': !r.sync_status }"
                                      x-text="{ accepted: 'Đã gửi', fake: 'Fake', failed: 'Lỗi' }[r.sync_status] || 'Chưa gửi'"></span>
                                <span x-show="r.synced_at" x-text="r.synced_at"></span>
                                <p class="text-error" x-show="r.sync_error" x-text="r.sync_error"></p>
                            </td>
                            <td><button type="button" class="btn btn-xs btn-ghost" @click="edit(r)">Sửa</button></td>
                        </tr>
                    </template>
                    <tr x-show="!loading && rows.length === 0"><td colspan="7" class="text-center text-base-content/40 py-8">Chưa có người giao hàng nào.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal" :class="{ 'modal-open': open }" @keydown.escape.window="open = false" x-cloak>
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-lg mb-4" x-text="editing ? 'Sửa người giao hàng' : 'Thêm người giao hàng'"></h3>
            <form @submit.prevent="save()" novalidate class="grid grid-cols-2 gap-3">
                <div class="form-control col-span-2">
                    <label class="label py-0.5"><span class="label-text text-sm font-medium">Họ tên *</span></label>
                    <input type="text" x-model="form.name" maxlength="255" class="input input-bordered input-sm w-full"/>
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-sm font-medium">Mã nhân sự</span></label>
                    <input type="text" x-model="form.code" :disabled="!!editing" maxlength="255" placeholder="Để trống: tự sinh GH-xxxxxx" class="input input-bordered input-sm w-full font-mono"/>
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-sm font-medium">Điện thoại</span></label>
                    <input type="text" x-model="form.phone" maxlength="20" class="input input-bordered input-sm w-full"/>
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-sm font-medium">Phương tiện</span></label>
                    <input type="text" x-model="form.transport_mean" maxlength="255" placeholder="VD: Xe tải 1.5 tấn" class="input input-bordered input-sm w-full"/>
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-sm font-medium">Biển số *</span></label>
                    <input type="text" x-model="form.license_plate" maxlength="255" placeholder="VD: 29C-123.45" class="input input-bordered input-sm w-full font-mono"/>
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-sm font-medium">Số CCCD</span></label>
                    <input type="text" x-model="form.cccd" maxlength="20" class="input input-bordered input-sm w-full"/>
                </div>
                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-sm font-medium">Ngày sinh</span></label>
                    <input type="date" x-model="form.date_of_birth" class="input input-bordered input-sm w-full"/>
                </div>
                <div class="form-control col-span-2">
                    <label class="label py-0.5"><span class="label-text text-sm font-medium">Địa chỉ</span></label>
                    <input type="text" x-model="form.address" maxlength="255" class="input input-bordered input-sm w-full"/>
                </div>
                <label class="col-span-2 flex items-center gap-2 text-sm cursor-pointer">
                    <input type="checkbox" class="checkbox checkbox-sm" x-model="form.is_active"/> Đang làm việc (bỏ tick để báo HanoiCheck đã nghỉ)
                </label>
                <p class="col-span-2 text-sm text-error" x-show="error" x-text="error"></p>
                <div class="col-span-2 modal-action mt-1">
                    <button type="button" class="btn btn-ghost btn-sm" @click="open = false">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="saving"><span x-show="saving" class="loading loading-spinner loading-xs"></span> Lưu</button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" @click="open = false"></div>
    </div>
</div>
@endsection

@push('styles')
    @vite(['Modules/HanoiCheckOrder/resources/assets/sass/hanoicheckorder.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite(['Modules/HanoiCheckOrder/resources/assets/js/hanoicheckorder.js'], 'build/backend')
@endpush
