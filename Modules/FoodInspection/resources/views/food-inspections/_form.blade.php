@php
    $isEdit = isset($log) && $log !== null;

    // Sửa: đổ các dòng hàng hiện có (kèm id để đối chiếu khi cập nhật). Có old() (lỗi validate) thì ưu tiên old().
    $initialRows = $isEdit
        ? $log->details->values()->mapWithKeys(fn ($d, $i) => [$i + 1 => [
            'id'                   => $d->id,
            'food_group'           => $d->food_group->value,
            'product_id'           => $d->product_id,
            'product_name'         => $d->product_name,
            'received_at'          => $d->received_at?->format('Y-m-d H:i:00'),
            'vendor_id'            => $d->vendor_id,
            'vendor_name'          => $d->vendor_name,
            'supplier_address'     => $d->supplier_address,
            'supplier_phone'       => $d->supplier_phone,
            'deliverer_name'       => $d->deliverer_name,
            'quantity'             => $d->quantity !== null ? rtrim(rtrim((string) $d->quantity, '0'), '.') : '',
            'unit'                 => $d->unit,
            'has_invoice'          => $d->has_invoice,
            'has_vet_cert'         => $d->has_vet_cert,
            'has_quarantine_cert'  => $d->has_quarantine_cert,
            'sensory_result'       => $d->sensory_result->value,
            'quick_test_result'    => $d->quick_test_result->value,
            'handling_measure'     => $d->handling_measure,
            'manufacturer_name'    => $d->manufacturer_name,
            'manufacturer_address' => $d->manufacturer_address,
            'expiry_date'          => $d->expiry_date?->format('Y-m-d'),
            'storage_condition'    => $d->storage_condition?->value,
        ]])->all()
        : [];
    $oldDetails = old('details') !== null ? (array) old('details') : $initialRows;
    $rowGroups  = collect($oldDetails)->map(fn ($r) => $r['food_group'] ?? 'fresh')->all();
    $rowsConfig = [
        'vendors'         => $options['vendors'],
        'nowLocal'        => now()->format('Y-m-d H:i:00'),
        'errs'            => $errors->keys(),
        'rows'            => (object) $oldDetails,
    ];
@endphp

<div x-data="{
    tab: 'general',
    tabFields: {
        general: ['inspected_at', 'customer_id', 'inspection_location', 'note', 'attachments'],
        fresh: [],
        dry: [],
    },
    rowGroups: {{ Js::from($rowGroups) }},
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        if (t === 'general') {
            return this.errs.filter(k => this.tabFields.general.some(f => k === f || k.startsWith(f + '.'))).length;
        }
        // Lỗi dòng hàng: details.{n}.{field} → gộp về tab của dòng đó; lỗi chung 'details' (thiếu dòng) về tab tươi sống.
        return this.errs.filter(k => (k === 'details' && t === 'fresh')
            || (k.startsWith('details.') && this.rowGroups[k.split('.')[1]] === t)).length;
    },
    init() {
        for (const t of Object.keys(this.tabFields)) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $isEdit ? 'Chỉnh sửa sổ kiểm thực Bước 1' : 'Tạo sổ kiểm thực Bước 1' }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Kiểm tra thực phẩm trước khi chế biến — Mẫu số 1, Phụ lục 1 (QĐ 1246/QĐ-BYT)</p>
    </div>
    <a href="{{ $isEdit ? route('backend.food-inspections.show', $log) : route('backend.food-inspections.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

{{-- Error banner --}}
@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.007M12 21a9 9 0 100-18 9 9 0 000 18z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ $isEdit ? route('backend.food-inspections.update', $log) : route('backend.food-inspections.store') }}" enctype="multipart/form-data" novalidate data-food-inspection-form>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div x-data="foodInspectionRows({{ Js::from($rowsConfig) }})" class="space-y-6">

        {{-- Khối thông tin (full width): tab nav + panels --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="border-b border-base-200 px-6">
                <nav class="flex -mb-px overflow-x-auto" role="tablist" aria-label="Form sections">

                    <button type="button" role="tab" :aria-selected="tab === 'general'" @click="tab = 'general'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                            :class="tab === 'general' ? 'border-primary text-primary' : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Thông tin chung
                        <span x-show="errCount('general') > 0" x-text="errCount('general')" class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'fresh'" @click="tab = 'fresh'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                            :class="tab === 'fresh' ? 'border-primary text-primary' : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Thực phẩm tươi sống, đông lạnh
                        <span class="badge badge-ghost badge-xs" x-text="freshRows.length"></span>
                        <span x-show="errCount('fresh') > 0" x-text="errCount('fresh')" class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'dry'" @click="tab = 'dry'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                            :class="tab === 'dry' ? 'border-primary text-primary' : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Thực phẩm khô, bao gói sẵn, gia vị
                        <span class="badge badge-ghost badge-xs" x-text="dryRows.length"></span>
                        <span x-show="errCount('dry') > 0" x-text="errCount('dry')" class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            <div class="p-6">

                {{-- ── Tab 1: Thông tin chung ─────────────────────────────── --}}
                <div x-show="tab === 'general'" data-tab-label="Thông tin chung" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Người kiểm tra</span>
                                <span class="label-text-alt text-base-content/40 text-xs">{{ $isEdit ? 'Người lập sổ ban đầu' : 'Tự động theo tài khoản' }}</span>
                            </label>
                            <input type="text" value="{{ $isEdit ? ($log->inspector?->name ?? '—') : auth()->user()->name }}" readonly
                                   class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="fp-inspected_at">
                                <span class="label-text font-medium">Thời gian kiểm tra <span class="text-error">*</span></span>
                            </label>
                            <input type="text" id="fp-inspected_at" name="inspected_at" data-fp-mode="datetime"
                                   value="{{ old('inspected_at', $isEdit ? $log->inspected_at?->format('Y-m-d H:i:00') : now()->format('Y-m-d H:i:00')) }}"
                                   data-req="Vui lòng chọn thời gian kiểm tra"
                                   class="input input-bordered input-sm w-full fp-init @error('inspected_at') input-error @enderror"
                                   placeholder="DD/MM/YYYY HH:mm">
                            @error('inspected_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5" for="ts-customer_id">
                                <span class="label-text font-medium">Khách hàng / Điểm phục vụ <span class="text-error">*</span></span>
                                <span class="label-text-alt text-base-content/40 text-xs">Lô nguyên liệu này dùng cho cơ sở nào — lấy từ Khách hàng</span>
                            </label>
                            {{-- Nằm trong panel x-show có thể đang ẩn → không dùng ts-init, khởi tạo thủ công (spec §22.6) --}}
                            @php $currentCustomerId = old('customer_id', $isEdit ? $log->customer_id : null); @endphp
                            <select id="ts-customer_id" name="customer_id" data-req="Vui lòng chọn khách hàng / điểm phục vụ"
                                    class="select select-bordered select-sm w-full @error('customer_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn khách hàng / điểm phục vụ —">
                                <option value="">— Chọn khách hàng / điểm phục vụ —</option>
                                @foreach($options['customers'] as $customer)
                                <option value="{{ $customer['value'] }}" data-address="{{ $customer['address'] }}" @selected($currentCustomerId === $customer['value'])>{{ $customer['text'] }}</option>
                                @endforeach
                                @if($isEdit && $log->customer_id && collect($options['customers'])->doesntContain('value', $log->customer_id))
                                <option value="{{ $log->customer_id }}" data-address="" @selected($currentCustomerId === $log->customer_id)>{{ $log->customer_name }}</option>
                                @endif
                            </select>
                            @error('customer_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5" for="inspection_location">
                                <span class="label-text font-medium">Địa điểm kiểm tra</span>
                                <span class="label-text-alt text-base-content/40 text-xs">Tự điền theo địa chỉ khách hàng, có thể sửa tay</span>
                            </label>
                            <input type="text" id="inspection_location" name="inspection_location" maxlength="255"
                                   value="{{ old('inspection_location', $isEdit ? $log->inspection_location : null) }}"
                                   class="input input-bordered input-sm w-full @error('inspection_location') input-error @enderror"
                                   placeholder="VD: Bếp ăn Trường Tiểu học Ngôi Sao">
                            @error('inspection_location')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="attachments">
                                <span class="label-text font-medium">Chứng từ chung cả chuyến</span>
                                <span class="label-text-alt text-base-content/40 text-xs">Ảnh/PDF, tối đa 10 file × 5MB</span>
                            </label>
                            <input type="file" id="attachments" name="attachments[]" multiple accept="image/*,application/pdf"
                                   class="file-input file-input-bordered file-input-sm w-full">
                            @if($isEdit && ! empty($log->attachments))
                            <div class="mt-2 space-y-1">
                                <p class="text-xs text-base-content/50">Chứng từ đã lưu — tick để gỡ khỏi sổ:</p>
                                @foreach($log->attachments as $file)
                                <label class="flex items-center gap-2 text-xs cursor-pointer">
                                    <input type="checkbox" name="remove_attachments[]" value="{{ $file['path'] }}" class="checkbox checkbox-xs checkbox-error"
                                           @checked(in_array($file['path'], (array) old('remove_attachments', []), true))>
                                    <a href="{{ Storage::disk('public')->url($file['path']) }}" target="_blank" rel="noopener" class="link link-primary">{{ $file['name'] }}</a>
                                </label>
                                @endforeach
                            </div>
                            @endif
                            @error('attachments')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @foreach($errors->get('attachments.*') as $messages)<p class="mt-1 text-xs text-error">{{ $messages[0] }}</p>@endforeach
                        </div>

                    </div>

                    <div class="form-control mt-4">
                        <label class="label py-0 pb-1.5" for="note">
                            <span class="label-text font-medium">Ghi chú</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Tuỳ chọn</span>
                        </label>
                        <textarea id="note" name="note" rows="3" maxlength="2000"
                                  class="textarea textarea-bordered textarea-sm w-full @error('note') textarea-error @enderror"
                                  placeholder="VD: Xe giao hàng đến muộn 15 phút">{{ old('note', $isEdit ? $log->note : null) }}</textarea>
                        @error('note')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'fresh'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Thực phẩm tươi sống
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                {{-- ── Tab 2: Mục I — Thực phẩm tươi sống, đông lạnh ───────── --}}
                <div x-show="tab === 'fresh'" x-cloak data-tab-label="Thực phẩm tươi sống, đông lạnh" class="space-y-4">
                    <p class="text-xs text-base-content/50">
                        Mỗi dòng gắn với một nhà cung cấp riêng (dòng mới tự copy NCC / người giao từ dòng trước). Mặc định mọi mục là <strong>Đạt / Có</strong> —
                        chỉ thao tác khi phát hiện hàng lỗi. Biện pháp xử lý chỉ nhập được khi Cảm quan hoặc Test nhanh là <strong>Không đạt</strong>.
                    </p>

                    <div class="overflow-x-auto">
                        <table class="table table-sm min-w-[1760px]">
                            <thead>
                                <tr class="text-xs">
                                    <th class="w-10">STT</th><th class="min-w-48">Tên thực phẩm <span class="text-error">*</span></th><th class="w-44">Thời gian nhập <span class="text-error">*</span></th>
                                    <th class="w-24">Khối lượng</th><th class="w-16">ĐVT</th>
                                    <th class="min-w-52">Tên cơ sở cung cấp <span class="text-error">*</span></th><th class="min-w-56">Địa chỉ &amp; số điện thoại</th><th class="min-w-40">Người giao hàng <span class="text-error">*</span></th>
                                    <th class="text-center w-16">Hóa đơn</th><th class="text-center w-16">VS thú y</th><th class="text-center w-16">Kiểm dịch</th>
                                    <th class="w-28">Cảm quan</th><th class="w-28">Test nhanh</th><th class="min-w-52">Biện pháp xử lý</th><th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, i) in freshRows" :key="row.n">
                                    <tr :class="{ 'bg-error/5': isFailed(row) }">
                                        <td class="text-xs text-base-content/50" x-text="i + 1"></td>
                                        <td>
                                            <input type="hidden" :name="`details[${row.n}][id]`" :value="row.id">
                                            <input type="hidden" :name="`details[${row.n}][food_group]`" value="fresh">
                                            <input type="hidden" :name="`details[${row.n}][product_id]`" :value="row.product_id">
                                            <input type="hidden" :name="`details[${row.n}][vendor_id]`" :value="row.vendor_id">
                                            <input type="hidden" :name="`details[${row.n}][has_invoice]`" :value="row.has_invoice ? 1 : 0">
                                            <input type="hidden" :name="`details[${row.n}][has_vet_cert]`" :value="row.has_vet_cert ? 1 : 0">
                                            <input type="hidden" :name="`details[${row.n}][has_quarantine_cert]`" :value="row.has_quarantine_cert ? 1 : 0">
                                            <input type="text" maxlength="255" :name="`details[${row.n}][product_name]`" x-model="row.product_name"
                                                   data-req="Vui lòng nhập tên thực phẩm" :data-label="`Tên thực phẩm (dòng ${i + 1})`"
                                                   class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="{ 'input-error': serverErr(row, 'product_name') }"
                                                   placeholder="VD: Thịt heo ba chỉ">
                                        </td>
                                        <td>
                                            <input type="text" :name="`details[${row.n}][received_at]`" x-model="row.received_at" x-fp.datetime="row.received_at"
                                                   data-req="Vui lòng nhập thời gian nhập hàng" :data-label="`Thời gian nhập (dòng ${i + 1})`"
                                                   class="input input-bordered input-xs w-full" placeholder="DD/MM/YYYY HH:mm">
                                        </td>
                                        <td><input type="number" step="0.001" min="0" :name="`details[${row.n}][quantity]`" x-model="row.quantity" class="input input-bordered input-xs w-full placeholder:text-gray-300" placeholder="VD: 12.5"></td>
                                        <td><input type="text" maxlength="50" :name="`details[${row.n}][unit]`" x-model="row.unit" class="input input-bordered input-xs w-full placeholder:text-gray-300" placeholder="VD: kg"></td>
                                        <td>
                                            <input type="hidden" :name="`details[${row.n}][vendor_name]`" :value="row.vendor_name">
                                            <select x-vts="row" class="select select-bordered select-xs w-full"
                                                    data-req="Vui lòng chọn hoặc nhập tên cơ sở cung cấp" :data-label="`Cơ sở cung cấp (dòng ${i + 1})`"
                                                    :class="{ 'select-error': serverErr(row, 'vendor_name') }">
                                                <option value=""></option>
                                            </select>
                                        </td>
                                        <td class="space-y-1">
                                            <input type="text" maxlength="500" :name="`details[${row.n}][supplier_address]`" x-model="row.supplier_address"
                                                   :readonly="row.vendor_id !== ''" placeholder="Địa chỉ..."
                                                   class="input input-bordered input-xs w-full text-xs placeholder:text-gray-300"
                                                   :class="row.vendor_id !== '' ? 'bg-gray-50 text-gray-500 cursor-default' : ''">
                                            <input type="text" maxlength="20" :name="`details[${row.n}][supplier_phone]`" x-model="row.supplier_phone"
                                                   :readonly="row.vendor_id !== ''" placeholder="Số điện thoại..."
                                                   class="input input-bordered input-xs w-full text-xs placeholder:text-gray-300"
                                                   :class="row.vendor_id !== '' ? 'bg-gray-50 text-gray-500 cursor-default' : ''">
                                        </td>
                                        <td>
                                            <input type="text" maxlength="255" :name="`details[${row.n}][deliverer_name]`" x-model="row.deliverer_name"
                                                   data-req="Vui lòng nhập tên người giao hàng" :data-label="`Người giao hàng (dòng ${i + 1})`"
                                                   class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="{ 'input-error': serverErr(row, 'deliverer_name') }"
                                                   placeholder="VD: Nguyễn Văn A">
                                        </td>
                                        <td class="text-center"><input type="checkbox" class="checkbox checkbox-success checkbox-xs" x-model="row.has_invoice"></td>
                                        <td class="text-center"><input type="checkbox" class="checkbox checkbox-success checkbox-xs" x-model="row.has_vet_cert"></td>
                                        <td class="text-center"><input type="checkbox" class="checkbox checkbox-success checkbox-xs" x-model="row.has_quarantine_cert"></td>
                                        <td>
                                            <select :name="`details[${row.n}][sensory_result]`" x-model="row.sensory_result" x-ts="row.sensory_result" @change="onResultChange(row)"
                                                    class="select select-bordered select-xs w-full">
                                                <option value="pass">Đạt</option><option value="fail">Không đạt</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select :name="`details[${row.n}][quick_test_result]`" x-model="row.quick_test_result" x-ts="row.quick_test_result" @change="onResultChange(row)"
                                                    class="select select-bordered select-xs w-full">
                                                <option value="none">Không có</option><option value="pass">Đạt</option><option value="fail">Không đạt</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" maxlength="1000" :name="`details[${row.n}][handling_measure]`" x-model="row.handling_measure"
                                                   :disabled="!isFailed(row)" :data-req="isFailed(row) ? 'Vui lòng ghi biện pháp xử lý' : false"
                                                   :data-label="`Biện pháp xử lý (dòng ${i + 1})`"
                                                   :placeholder="isFailed(row) ? 'VD: Trả về nhà cung cấp' : '—'"
                                                   class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="{ 'input-error': serverErr(row, 'handling_measure') }">
                                        </td>
                                        <td><button type="button" class="btn btn-ghost btn-xs text-error" @click="removeRow(row)" title="Xóa dòng">✕</button></td>
                                    </tr>
                                </template>
                                <tr x-show="freshRows.length === 0"><td colspan="15" class="text-center text-sm text-base-content/40 py-6">Chưa có dòng nào — chọn phiếu nhập kho ở tab "Thông tin chung" hoặc bấm "Thêm dòng".</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm gap-1.5 text-primary" @click="addRow('fresh')">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Thêm dòng
                    </button>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'general'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Thông tin chung
                        </button>
                        <button type="button" @click="tab = 'dry'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Thực phẩm khô, bao gói
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                {{-- ── Tab 3: Mục II — Thực phẩm khô, bao gói sẵn, gia vị ──── --}}
                <div x-show="tab === 'dry'" x-cloak data-tab-label="Thực phẩm khô, bao gói sẵn, gia vị" class="space-y-4">
                    <p class="text-xs text-base-content/50">
                        Ngoài nhà cung cấp, cần ghi rõ cơ sở sản xuất, hạn sử dụng và điều kiện bảo quản (nhà cung cấp chưa chắc là nơi sản xuất).
                    </p>

                    <div class="overflow-x-auto">
                        <table class="table table-sm min-w-[1960px]">
                            <thead>
                                <tr class="text-xs">
                                    <th class="w-10">STT</th><th class="min-w-48">Tên thực phẩm <span class="text-error">*</span></th><th class="min-w-56">Tên &amp; địa chỉ NSX</th>
                                    <th class="w-44">Thời gian nhập <span class="text-error">*</span></th><th class="w-24">Khối lượng</th><th class="w-16">ĐVT</th>
                                    <th class="min-w-52">Tên cơ sở cung cấp <span class="text-error">*</span></th><th class="min-w-56">Địa chỉ &amp; số điện thoại</th><th class="min-w-40">Người giao hàng <span class="text-error">*</span></th>
                                    <th class="w-36">Hạn sử dụng</th><th class="w-40">ĐK bảo quản</th><th class="text-center w-16">Chứng từ</th>
                                    <th class="w-28">Cảm quan</th><th class="min-w-52">Biện pháp xử lý</th><th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, i) in dryRows" :key="row.n">
                                    <tr :class="{ 'bg-error/5': isFailed(row) }">
                                        <td class="text-xs text-base-content/50" x-text="i + 1"></td>
                                        <td>
                                            <input type="hidden" :name="`details[${row.n}][id]`" :value="row.id">
                                            <input type="hidden" :name="`details[${row.n}][food_group]`" value="dry">
                                            <input type="hidden" :name="`details[${row.n}][product_id]`" :value="row.product_id">
                                            <input type="hidden" :name="`details[${row.n}][vendor_id]`" :value="row.vendor_id">
                                            <input type="hidden" :name="`details[${row.n}][has_invoice]`" :value="row.has_invoice ? 1 : 0">
                                            <input type="text" maxlength="255" :name="`details[${row.n}][product_name]`" x-model="row.product_name"
                                                   data-req="Vui lòng nhập tên thực phẩm" :data-label="`Tên thực phẩm (dòng ${i + 1})`"
                                                   class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="{ 'input-error': serverErr(row, 'product_name') }"
                                                   placeholder="VD: Gạo ST25">
                                        </td>
                                        <td class="space-y-1">
                                            <input type="text" maxlength="255" :name="`details[${row.n}][manufacturer_name]`" x-model="row.manufacturer_name" placeholder="VD: Công ty Gạo Sóc Trăng" class="input input-bordered input-xs w-full placeholder:text-gray-300">
                                            <input type="text" maxlength="500" :name="`details[${row.n}][manufacturer_address]`" x-model="row.manufacturer_address" placeholder="Địa chỉ sản xuất" class="input input-bordered input-xs w-full placeholder:text-gray-300">
                                        </td>
                                        <td>
                                            <input type="text" :name="`details[${row.n}][received_at]`" x-model="row.received_at" x-fp.datetime="row.received_at"
                                                   data-req="Vui lòng nhập thời gian nhập hàng" :data-label="`Thời gian nhập (dòng ${i + 1})`"
                                                   class="input input-bordered input-xs w-full" placeholder="DD/MM/YYYY HH:mm">
                                        </td>
                                        <td><input type="number" step="0.001" min="0" :name="`details[${row.n}][quantity]`" x-model="row.quantity" class="input input-bordered input-xs w-full placeholder:text-gray-300" placeholder="VD: 50"></td>
                                        <td><input type="text" maxlength="50" :name="`details[${row.n}][unit]`" x-model="row.unit" class="input input-bordered input-xs w-full placeholder:text-gray-300" placeholder="VD: kg"></td>
                                        <td>
                                            <input type="hidden" :name="`details[${row.n}][vendor_name]`" :value="row.vendor_name">
                                            <select x-vts="row" class="select select-bordered select-xs w-full"
                                                    data-req="Vui lòng chọn hoặc nhập tên cơ sở cung cấp" :data-label="`Cơ sở cung cấp (dòng ${i + 1})`"
                                                    :class="{ 'select-error': serverErr(row, 'vendor_name') }">
                                                <option value=""></option>
                                            </select>
                                        </td>
                                        <td class="space-y-1">
                                            <input type="text" maxlength="500" :name="`details[${row.n}][supplier_address]`" x-model="row.supplier_address"
                                                   :readonly="row.vendor_id !== ''" placeholder="Địa chỉ..."
                                                   class="input input-bordered input-xs w-full text-xs placeholder:text-gray-300"
                                                   :class="row.vendor_id !== '' ? 'bg-gray-50 text-gray-500 cursor-default' : ''">
                                            <input type="text" maxlength="20" :name="`details[${row.n}][supplier_phone]`" x-model="row.supplier_phone"
                                                   :readonly="row.vendor_id !== ''" placeholder="Số điện thoại..."
                                                   class="input input-bordered input-xs w-full text-xs placeholder:text-gray-300"
                                                   :class="row.vendor_id !== '' ? 'bg-gray-50 text-gray-500 cursor-default' : ''">
                                        </td>
                                        <td>
                                            <input type="text" maxlength="255" :name="`details[${row.n}][deliverer_name]`" x-model="row.deliverer_name"
                                                   data-req="Vui lòng nhập tên người giao hàng" :data-label="`Người giao hàng (dòng ${i + 1})`"
                                                   class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="{ 'input-error': serverErr(row, 'deliverer_name') }"
                                                   placeholder="VD: Nguyễn Văn A">
                                        </td>
                                        <td><input type="text" :name="`details[${row.n}][expiry_date]`" x-model="row.expiry_date" x-fp="row.expiry_date" class="input input-bordered input-xs w-full" placeholder="DD/MM/YYYY"></td>
                                        <td>
                                            <select :name="`details[${row.n}][storage_condition]`" x-model="row.storage_condition" x-ts="row.storage_condition" class="select select-bordered select-xs w-full">
                                                <option value="ambient">Nhiệt độ thường</option><option value="cold">Lạnh</option>
                                            </select>
                                        </td>
                                        <td class="text-center"><input type="checkbox" class="checkbox checkbox-success checkbox-xs" x-model="row.has_invoice"></td>
                                        <td>
                                            <select :name="`details[${row.n}][sensory_result]`" x-model="row.sensory_result" x-ts="row.sensory_result" @change="onResultChange(row)"
                                                    class="select select-bordered select-xs w-full">
                                                <option value="pass">Đạt</option><option value="fail">Không đạt</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" maxlength="1000" :name="`details[${row.n}][handling_measure]`" x-model="row.handling_measure"
                                                   :disabled="!isFailed(row)" :data-req="isFailed(row) ? 'Vui lòng ghi biện pháp xử lý' : false"
                                                   :data-label="`Biện pháp xử lý (dòng ${i + 1})`"
                                                   :placeholder="isFailed(row) ? 'VD: Trả về nhà cung cấp' : '—'"
                                                   class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="{ 'input-error': serverErr(row, 'handling_measure') }">
                                        </td>
                                        <td><button type="button" class="btn btn-ghost btn-xs text-error" @click="removeRow(row)" title="Xóa dòng">✕</button></td>
                                    </tr>
                                </template>
                                <tr x-show="dryRows.length === 0"><td colspan="15" class="text-center text-sm text-base-content/40 py-6">Chưa có dòng nào — chọn phiếu nhập kho ở tab "Thông tin chung" hoặc bấm "Thêm dòng".</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm gap-1.5 text-primary" @click="addRow('dry')">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Thêm dòng
                    </button>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'fresh'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Thực phẩm tươi sống
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>{{ $isEdit ? 'Lưu lại' : 'Tạo sổ kiểm thực' }}</strong> ở bên dưới khi xong</span>
                    </div>
                </div>

            </div>
        </div>

        {{-- Khối Publish (full width): tóm tắt + hành động lưu --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">

                    <div>
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Tóm tắt</p>
                        @if($isEdit)
                        <div class="flex gap-4 text-xs text-base-content/40 mb-2">
                            <span>Tạo {{ $log->created_at?->format('d/m/Y H:i') }}</span>
                            <span>Sửa lần cuối {{ $log->updated_at?->diffForHumans() }}</span>
                        </div>
                        @endif
                        <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs">
                            <span><span class="text-base-content/50">Tươi sống, đông lạnh:</span> <span class="font-mono" x-text="freshRows.length"></span></span>
                            <span><span class="text-base-content/50">Khô, bao gói, gia vị:</span> <span class="font-mono" x-text="dryRows.length"></span></span>
                            <span><span class="text-base-content/50">Không đạt:</span> <span class="font-mono" :class="failedCount > 0 ? 'text-error font-semibold' : ''" x-text="failedCount"></span></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <p class="text-xs text-base-content/30"><span class="text-error">*</span> là trường bắt buộc</p>
                        <div class="flex gap-2">
                            <a href="{{ $isEdit ? route('backend.food-inspections.show', $log) : route('backend.food-inspections.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                            <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="submitting">
                                <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                                <svg x-show="!submitting" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $isEdit ? 'M5 13l4 4L19 7' : 'M12 4v16m8-8H4' }}"/></svg>
                                <span x-text="submitting ? 'Đang xử lý...' : {{ Js::from($isEdit ? 'Lưu lại' : 'Tạo sổ kiểm thực') }}"></span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</form>
</div>
