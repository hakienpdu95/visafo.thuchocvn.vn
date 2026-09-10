@extends('layouts.backend')
@section('title', $customer->name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            {{ $customer->name }}
            <span class="badge {{ $customer->status->badgeClass() }} badge-sm">{{ $customer->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5 font-mono">
            {{ $customer->customer_code ?? '—' }} · {{ $customer->customer_group->label() }} · {{ $customer->meal_model->label() }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.customers.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        @can('update', $customer)
        <a href="{{ route('backend.customers.edit', $customer) }}" class="btn btn-primary btn-sm">Chỉnh sửa</a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6 items-start"
     x-data="{ tab: '{{ $errors->has('site_name') || $errors->has('address') ? 'delivery' : 'contacts' }}' }">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">

            <div role="tablist" class="tabs tabs-boxed mb-4 w-fit">
                <a role="tab" class="tab" :class="tab === 'contacts' ? 'tab-active' : ''" @click.prevent="tab = 'contacts'" href="#">
                    Đầu mối liên hệ
                </a>
                <a role="tab" class="tab" :class="tab === 'delivery' ? 'tab-active' : ''" @click.prevent="tab = 'delivery'" href="#">
                    Địa điểm giao hàng
                </a>
            </div>

            {{-- ── Tab: Đầu mối liên hệ ─────────────────────────────────── --}}
            <div x-show="tab === 'contacts'">

                @if($customer->contacts->isEmpty())
                <p class="text-sm text-base-content/50 mb-5">Chưa có đầu mối liên hệ nào được ghi nhận.</p>
                @else
                <div class="overflow-x-auto mb-5">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Họ và tên</th>
                                <th>Chức vụ</th>
                                <th>Điện thoại</th>
                                <th>Email</th>
                                <th>Ghi chú</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customer->contacts as $contact)
                            <tr>
                                <td class="font-medium">{{ $contact->name }}</td>
                                <td>{{ $contact->title ?? '—' }}</td>
                                <td>{{ $contact->phone ?? '—' }}</td>
                                <td>{{ $contact->email ?? '—' }}</td>
                                <td class="text-xs text-base-content/50">{{ $contact->note ?? '—' }}</td>
                                <td>
                                    @can('update', $customer)
                                    <form method="POST" action="{{ route('backend.customers.contacts.destroy', [$customer, $contact]) }}"
                                          onsubmit="return confirm('Xóa đầu mối liên hệ này?');">
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

                @can('update', $customer)
                <div class="bg-base-200/40 rounded-box p-4">
                    <h3 class="text-sm font-semibold mb-3">Thêm đầu mối liên hệ mới</h3>
                    <form method="POST" action="{{ route('backend.customers.contacts.store', $customer) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @csrf
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Họ và tên <span class="text-error">*</span></span></label>
                            <input type="text" name="name" value="{{ old('name') }}" class="input input-bordered input-sm w-full" placeholder="VD: Trần Thị B">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Chức vụ</span></label>
                            <input type="text" name="title" value="{{ old('title') }}" class="input input-bordered input-sm w-full" placeholder="VD: Kế toán, Bếp trưởng, Quản lý cơ sở">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Điện thoại</span></label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="input input-bordered input-sm w-full" placeholder="09xx xxx xxx">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Email</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" class="input input-bordered input-sm w-full" placeholder="email@example.com">
                        </div>
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ghi chú</span></label>
                            <input type="text" name="note" value="{{ old('note') }}" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn btn-primary btn-sm w-full sm:w-auto">Thêm đầu mối liên hệ</button>
                        </div>
                    </form>
                </div>
                @endcan
            </div>

            {{-- ── Tab: Địa điểm giao hàng ──────────────────────────────── --}}
            <div x-show="tab === 'delivery'" style="display: none;">

                @if($customer->deliveryPoints->isEmpty())
                <p class="text-sm text-base-content/50 mb-5">Chưa có điểm giao hàng nào được ghi nhận.</p>
                @else
                <div class="overflow-x-auto mb-5">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tên cơ sở</th>
                                <th>Địa chỉ</th>
                                <th>Người nhận hàng</th>
                                <th>SĐT nhận hàng</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customer->deliveryPoints as $point)
                            <tr>
                                <td class="font-medium">{{ $point->site_name }}</td>
                                <td>
                                    {{ $point->address }}
                                    @if($point->ward || $point->province)
                                    <br><span class="text-xs text-base-content/50">{{ trim(($point->ward?->name ?? '') . ', ' . ($point->province?->name ?? ''), ', ') }}</span>
                                    @endif
                                </td>
                                <td>{{ $point->receiver_name ?? '—' }}</td>
                                <td>{{ $point->receiver_phone ?? '—' }}</td>
                                <td>
                                    @can('update', $customer)
                                    <form method="POST" action="{{ route('backend.customers.delivery-points.destroy', [$customer, $point]) }}"
                                          onsubmit="return confirm('Xóa điểm giao hàng này?');">
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

                @can('update', $customer)
                <div class="bg-base-200/40 rounded-box p-4">
                    <h3 class="text-sm font-semibold mb-3">Thêm điểm giao hàng mới</h3>
                    <form method="POST" action="{{ route('backend.customers.delivery-points.store', $customer) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @csrf
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tên cơ sở <span class="text-error">*</span></span></label>
                            <input type="text" name="site_name" value="{{ old('site_name') }}" class="input input-bordered input-sm w-full @error('site_name') input-error @enderror" placeholder="VD: Marie Curie Cơ sở 1">
                            @error('site_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Địa chỉ giao hàng <span class="text-error">*</span></span></label>
                            <input type="text" name="address" value="{{ old('address') }}" class="input input-bordered input-sm w-full @error('address') input-error @enderror" placeholder="VD: 456 Lê Văn Sỹ, Phường 3">
                            @error('address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control sm:col-span-2">
                            <x-address-picker
                                :province-value="old('province_code')"
                                :ward-value="old('ward_code')"
                                instance-id="customer-dp"
                            />
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Người nhận hàng</span></label>
                            <input type="text" name="receiver_name" value="{{ old('receiver_name') }}" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">SĐT người nhận</span></label>
                            <input type="text" name="receiver_phone" value="{{ old('receiver_phone') }}" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ghi chú</span></label>
                            <input type="text" name="note" value="{{ old('note') }}" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn btn-primary btn-sm w-full sm:w-auto">Thêm điểm giao hàng</button>
                        </div>
                    </form>
                </div>
                @endcan
            </div>

        </div>
    </div>

    <div class="space-y-6">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Thông tin pháp nhân</h2>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-base-content/50 text-xs">Nhóm khách hàng</dt><dd>{{ $customer->customer_group->label() }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Mô hình tổ chức bữa ăn</dt><dd>{{ $customer->meal_model->label() }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Mã số thuế</dt><dd>{{ $customer->tax_code ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Địa chỉ</dt>
                        <dd>
                            {{ $customer->address ?? '—' }}
                            @if($customer->ward || $customer->province)
                                <br>{{ trim(($customer->ward?->name ?? '') . ', ' . ($customer->province?->name ?? ''), ', ') }}
                            @endif
                        </dd>
                    </div>
                    <div><dt class="text-base-content/50 text-xs">Điện thoại</dt><dd>{{ $customer->phone_number ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Email</dt><dd>{{ $customer->email ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Người đại diện theo pháp luật</h2>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-base-content/50 text-xs">Họ và tên</dt><dd>{{ $customer->representative_name ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Chức danh</dt><dd>{{ $customer->representative_title ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Điện thoại</dt><dd>{{ $customer->representative_phone ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/50 text-xs">Email</dt><dd>{{ $customer->representative_email ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="text-base font-semibold mb-3">Nhân viên phụ trách</h2>
                <p class="text-sm">{{ $customer->pic?->full_name ?? 'Chưa phân công' }}</p>
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
    @vite(['Modules/Customer/resources/assets/sass/customer.scss'], 'build/backend')
@endpush
