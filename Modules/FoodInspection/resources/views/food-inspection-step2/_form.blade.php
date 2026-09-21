@php
    $isEdit = isset($log) && $log !== null;

    // Sửa: đổ các dòng hiện có (kèm id). Có old() (lỗi validate) thì ưu tiên old().
    $initialRows = $isEdit
        ? $log->details->values()->mapWithKeys(fn ($d, $i) => [$i + 1 => [
            'id'                => $d->id,
            'menu_dish_id'      => $d->menu_dish_id,
            'meal_time'         => $d->meal_time->value,
            'dish_name'         => $d->dish_name,
            'main_ingredients'  => $d->main_ingredients,
            'quantity'          => $d->quantity,
            'prep_time'         => $d->prep_time ? substr($d->prep_time, 0, 5) : '',
            'cook_time'         => $d->cook_time ? substr($d->cook_time, 0, 5) : '',
            'hygiene_personnel' => $d->hygiene_personnel,
            'hygiene_equipment' => $d->hygiene_equipment,
            'hygiene_area'      => $d->hygiene_area,
            'sensory_eval'      => $d->sensory_eval,
            'action_taken'      => $d->action_taken,
        ]])->all()
        : [];
    $rowsSource = old('details') !== null ? (array) old('details') : $initialRows;
    $rowsConfig = collect($rowsSource)->map(fn ($r, $n) => ['n' => (int) $n] + (array) $r)->values()->all();

    $currentCustomerId = old('customer_id', $isEdit ? $log->customer_id : null);
    $currentLocation   = old('location_name', $isEdit ? $log->location_name : null);
    $step2Config = ['locations' => $locations];
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $isEdit ? 'Chỉnh sửa sổ kiểm thực Bước 2' : 'Tạo sổ kiểm thực Bước 2' }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Kiểm tra khi chế biến — Mẫu số 2, Phụ lục 1 (QĐ 1246/QĐ-BYT)</p>
    </div>
    <a href="{{ $isEdit ? route('backend.food-inspection-step2.show', $log) : route('backend.food-inspection-step2.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.007M12 21a9 9 0 100-18 9 9 0 000 18z"/></svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
</div>
@endif

<form method="POST" action="{{ $isEdit ? route('backend.food-inspection-step2.update', $log) : route('backend.food-inspection-step2.store') }}" novalidate
      data-step2-form data-step2-config="{{ json_encode($step2Config, JSON_UNESCAPED_UNICODE) }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div x-data="step2Form({{ Js::from([
            'meals'       => $meals,
            'rows'        => $rowsConfig,
            'customerId'  => $currentCustomerId ?? '',
            'defaultMeal' => 'lunch',
            'syncUrl'     => route('backend.food-inspection-step2.menu-dishes'),
        ]) }})" class="space-y-6">

        {{-- Khối 1: thông tin chung + đồng bộ thực đơn --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Thông tin chung</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Người kiểm tra</span>
                            <span class="label-text-alt text-base-content/40 text-xs">{{ $isEdit ? 'Người lập sổ ban đầu' : 'Tự động theo tài khoản' }}</span>
                        </label>
                        <input type="text" value="{{ $isEdit ? $log->inspector_name : auth()->user()->name }}" readonly
                               class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="fp-inspection_date">
                            <span class="label-text font-medium">Ngày kiểm tra <span class="text-error">*</span></span>
                        </label>
                        <input type="text" id="fp-inspection_date" name="inspection_date" data-req="Vui lòng chọn ngày kiểm tra"
                               value="{{ old('inspection_date', $isEdit ? $log->inspection_date?->format('Y-m-d') : now()->format('Y-m-d')) }}"
                               class="input input-bordered input-sm w-full fp-init @error('inspection_date') input-error @enderror" placeholder="DD/MM/YYYY">
                        @error('inspection_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="ts-customer_id">
                            <span class="label-text font-medium">Tên cơ sở / Doanh nghiệp suất ăn <span class="text-error">*</span></span>
                            <span class="label-text-alt text-base-content/40 text-xs">Lấy từ Khách hàng</span>
                        </label>
                        <select id="ts-customer_id" name="customer_id" data-req="Vui lòng chọn cơ sở / doanh nghiệp suất ăn"
                                class="select select-bordered select-sm w-full ts-init @error('customer_id') select-error @enderror"
                                data-ts-placeholder="— Chọn cơ sở —">
                            <option value="">— Chọn cơ sở —</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer['value'] }}" @selected($currentCustomerId === $customer['value'])>{{ $customer['text'] }}</option>
                            @endforeach
                            @if($isEdit && collect($customers)->doesntContain('value', $log->customer_id))
                            <option value="{{ $log->customer_id }}" selected>{{ $log->customer_name }}</option>
                            @endif
                        </select>
                        @error('customer_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="ts-location_name">
                            <span class="label-text font-medium">Địa điểm</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Chọn gợi ý hoặc gõ tên mới</span>
                        </label>
                        {{-- Combobox khởi tạo thủ công (gợi ý phụ thuộc cơ sở đang chọn) — không dùng ts-init --}}
                        <select id="ts-location_name" name="location_name" data-initial="{{ $currentLocation }}"
                                class="select select-bordered select-sm w-full @error('location_name') select-error @enderror">
                            <option value=""></option>
                        </select>
                        @error('location_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="ts-sync_meal">
                            <span class="label-text font-medium">Đồng bộ Thực đơn</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Đổ món ăn của ngày + bữa ăn đã chọn xuống lưới</span>
                        </label>
                        <div class="flex flex-wrap items-start gap-2">
                            <div class="w-56">
                                <select id="ts-sync_meal" class="select select-bordered select-sm w-full ts-init" data-ts-placeholder="— Chọn bữa ăn —">
                                    @foreach($meals as $meal)
                                    <option value="{{ $meal['value'] }}" @selected($meal['value'] === 'lunch')>{{ $meal['text'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-outline btn-primary btn-sm gap-1.5" @click="syncMenu()" :disabled="syncing">
                                <span x-show="syncing" class="loading loading-spinner loading-xs"></span>
                                <svg x-show="!syncing" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Đồng bộ Thực đơn
                            </button>
                            <p class="text-xs self-center" :class="syncOk ? 'text-success' : 'text-warning'" x-show="syncMessage" x-text="syncMessage"></p>
                        </div>
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="note"><span class="label-text font-medium">Ghi chú</span><span class="label-text-alt text-base-content/40 text-xs">Tuỳ chọn</span></label>
                        <input type="text" id="note" name="note" maxlength="2000" value="{{ old('note', $isEdit ? $log->note : null) }}"
                               class="input input-bordered input-sm w-full @error('note') input-error @enderror" placeholder="VD: Ca trưa có đoàn thanh tra đột xuất">
                        @error('note')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Khối 2: lưới kiểm tra khi chế biến --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-1">Kiểm tra khi chế biến</h2>
                <p class="text-xs text-base-content/50 mb-4">
                    Ca/bữa, tên món, nguyên liệu, số suất tự điền từ thực đơn (chỉ đọc). Vệ sinh và cảm quan mặc định <strong>Đạt</strong> — bỏ tick khi phát hiện vi phạm để mở ô Biện pháp xử lý.
                    Bấm biểu tượng đồng hồ (hoặc nhấp đúp ô giờ) để điền giờ hiện tại.
                </p>

                <div class="overflow-x-auto">
                    <table class="table table-sm min-w-[1620px]">
                        <thead>
                            <tr class="text-xs">
                                <th class="w-10">STT</th><th class="w-40">Ca/bữa ăn</th><th class="min-w-48">Tên món ăn <span class="text-error">*</span></th>
                                <th class="min-w-56">Nguyên liệu chính</th><th class="w-24">Số suất</th>
                                <th class="w-36">Sơ chế xong</th><th class="w-36">Chế biến xong</th>
                                <th class="text-center w-20">VS người</th><th class="text-center w-20">VS thiết bị</th><th class="text-center w-20">VS khu vực</th><th class="text-center w-20">Cảm quan</th>
                                <th class="min-w-56">Biện pháp xử lý</th><th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in rows" :key="row.n">
                                <tr :class="{ 'bg-error/5': isFailed(row) }">
                                    <td class="text-xs text-base-content/50" x-text="i + 1"></td>
                                    <td>
                                        <input type="hidden" :name="`details[${row.n}][id]`" :value="row.id">
                                        <input type="hidden" :name="`details[${row.n}][menu_dish_id]`" :value="row.menu_dish_id">
                                        <input type="hidden" :name="`details[${row.n}][meal_time]`" :value="row.meal_time">
                                        <input type="hidden" :name="`details[${row.n}][hygiene_personnel]`" :value="row.hygiene_personnel ? 1 : 0">
                                        <input type="hidden" :name="`details[${row.n}][hygiene_equipment]`" :value="row.hygiene_equipment ? 1 : 0">
                                        <input type="hidden" :name="`details[${row.n}][hygiene_area]`" :value="row.hygiene_area ? 1 : 0">
                                        <input type="hidden" :name="`details[${row.n}][sensory_eval]`" :value="row.sensory_eval ? 1 : 0">
                                        <template x-if="row.menu_dish_id !== ''">
                                            <input type="text" :value="mealLabel(row.meal_time)" readonly class="input input-bordered input-xs w-full text-xs bg-gray-50 text-gray-500 cursor-default">
                                        </template>
                                        <template x-if="row.menu_dish_id === ''">
                                            <select x-model="row.meal_time" x-ts="row.meal_time" class="select select-bordered select-xs w-full">
                                                <template x-for="m in meals" :key="m.value"><option :value="m.value" x-text="m.text"></option></template>
                                            </select>
                                        </template>
                                    </td>
                                    <td>
                                        <input type="text" maxlength="255" :name="`details[${row.n}][dish_name]`" x-model="row.dish_name" :readonly="row.menu_dish_id !== ''"
                                               data-req="Vui lòng nhập tên món ăn" :data-label="`Tên món ăn (dòng ${i + 1})`"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="row.menu_dish_id !== '' ? 'bg-gray-50 text-gray-500 cursor-default' : ''"
                                               placeholder="VD: Thịt xào su su">
                                    </td>
                                    <td>
                                        <input type="text" maxlength="500" :name="`details[${row.n}][main_ingredients]`" x-model="row.main_ingredients" :readonly="row.menu_dish_id !== ''"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="row.menu_dish_id !== '' ? 'bg-gray-50 text-gray-500 cursor-default' : ''"
                                               placeholder="VD: Thịt lợn, su su">
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="1" :name="`details[${row.n}][quantity]`" x-model="row.quantity" :readonly="row.menu_dish_id !== ''"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="row.menu_dish_id !== '' ? 'bg-gray-50 text-gray-500 cursor-default' : ''"
                                               placeholder="VD: 1740">
                                    </td>
                                    <td>
                                        <div class="join w-full">
                                            <input type="time" :name="`details[${row.n}][prep_time]`" x-model="row.prep_time" @dblclick="setNow(row, 'prep_time')"
                                                   class="input input-bordered input-xs join-item w-full min-w-0">
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" @click="setNow(row, 'prep_time')" title="Lấy giờ hiện tại">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="join w-full">
                                            <input type="time" :name="`details[${row.n}][cook_time]`" x-model="row.cook_time" @dblclick="setNow(row, 'cook_time')"
                                                   class="input input-bordered input-xs join-item w-full min-w-0">
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" @click="setNow(row, 'cook_time')" title="Lấy giờ hiện tại">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-center"><input type="checkbox" class="checkbox checkbox-success checkbox-xs" x-model="row.hygiene_personnel" @change="onCheckChange(row)"></td>
                                    <td class="text-center"><input type="checkbox" class="checkbox checkbox-success checkbox-xs" x-model="row.hygiene_equipment" @change="onCheckChange(row)"></td>
                                    <td class="text-center"><input type="checkbox" class="checkbox checkbox-success checkbox-xs" x-model="row.hygiene_area" @change="onCheckChange(row)"></td>
                                    <td class="text-center"><input type="checkbox" class="checkbox checkbox-success checkbox-xs" x-model="row.sensory_eval" @change="onCheckChange(row)"></td>
                                    <td>
                                        <input type="text" maxlength="1000" :name="`details[${row.n}][action_taken]`" x-model="row.action_taken"
                                               :disabled="!isFailed(row)" :data-req="isFailed(row) ? 'Vui lòng ghi biện pháp xử lý' : false"
                                               :data-label="`Biện pháp xử lý (dòng ${i + 1})`" :placeholder="isFailed(row) ? 'VD: Làm lại / loại bỏ món' : '—'"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300">
                                    </td>
                                    <td><button type="button" class="btn btn-ghost btn-xs text-error" @click="removeRow(row)" title="Xóa dòng">✕</button></td>
                                </tr>
                            </template>
                            <tr x-show="rows.length === 0"><td colspan="13" class="text-center text-sm text-base-content/40 py-6">Chưa có dòng nào — chọn cơ sở, ngày, bữa ăn rồi bấm "Đồng bộ Thực đơn" hoặc "Thêm dòng".</td></tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-ghost btn-sm gap-1.5 text-primary self-start mt-2" @click="addRow()">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Thêm dòng
                </button>
            </div>
        </div>

        {{-- Khối 3: Publish (full width) --}}
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
                            <span><span class="text-base-content/50">Số món:</span> <span class="font-mono" x-text="rows.length"></span></span>
                            <span><span class="text-base-content/50">Tổng số suất:</span> <span class="font-mono" x-text="totalServings"></span></span>
                            <span><span class="text-base-content/50">Không đạt:</span> <span class="font-mono" :class="failedCount > 0 ? 'text-error font-semibold' : ''" x-text="failedCount"></span></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-xs text-base-content/30"><span class="text-error">*</span> là trường bắt buộc</p>
                        <div class="flex gap-2">
                            <a href="{{ $isEdit ? route('backend.food-inspection-step2.show', $log) : route('backend.food-inspection-step2.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                            <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="submitting">
                                <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                                <span x-text="submitting ? 'Đang xử lý...' : {{ Js::from($isEdit ? 'Lưu lại' : 'Tạo sổ kiểm thực') }}"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
