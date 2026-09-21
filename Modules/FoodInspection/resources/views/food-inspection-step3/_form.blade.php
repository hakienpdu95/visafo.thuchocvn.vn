@php
    $isEdit = isset($log) && $log !== null;

    // Sửa: đổ các dòng hiện có (kèm id). Có old() (lỗi validate) thì ưu tiên old().
    $initialRows = $isEdit
        ? $log->details->values()->mapWithKeys(fn ($d, $i) => [$i + 1 => [
            'id'              => $d->id,
            'step2_detail_id' => $d->step2_detail_id,
            'menu_dish_id'    => $d->menu_dish_id,
            'source'          => $d->step2_detail_id ? 'step2' : ($d->menu_dish_id ? 'menu' : ''),
            'meal_time'       => $d->meal_time->value,
            'dish_name'       => $d->dish_name,
            'quantity'        => $d->quantity,
            'portion_time'    => $d->portion_time ? substr($d->portion_time, 0, 5) : '',
            'eat_time'        => $d->eat_time ? substr($d->eat_time, 0, 5) : '',
            'equipment_used'  => $d->equipment_used,
            'sensory_eval'    => $d->sensory_eval,
            'action_taken'    => $d->action_taken,
        ]])->all()
        : [];
    $rowsSource = old('details') !== null ? (array) old('details') : $initialRows;
    $rowsConfig = collect($rowsSource)->map(function ($r, $n) {
        $r = (array) $r;
        // old() không có "source" → suy ra từ khóa liên kết để giữ khóa dòng đã đồng bộ.
        $r['source'] = $r['source'] ?? (! empty($r['step2_detail_id']) ? 'step2' : (! empty($r['menu_dish_id']) ? 'menu' : ''));

        return ['n' => (int) $n] + $r;
    })->values()->all();

    $currentCustomerId = old('customer_id', $isEdit ? $log->customer_id : null);
    $currentLocation   = old('location_name', $isEdit ? $log->location_name : null);
    $step3Config = ['locations' => $locations];
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $isEdit ? 'Chỉnh sửa sổ kiểm thực Bước 3' : 'Tạo sổ kiểm thực Bước 3' }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Kiểm tra trước khi ăn — Mẫu số 3, Phụ lục 1 (QĐ 1246/QĐ-BYT)</p>
    </div>
    <a href="{{ $isEdit ? route('backend.food-inspection-step3.show', $log) : route('backend.food-inspection-step3.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ $isEdit ? route('backend.food-inspection-step3.update', $log) : route('backend.food-inspection-step3.store') }}" novalidate
      data-step3-form data-step3-config="{{ json_encode($step3Config, JSON_UNESCAPED_UNICODE) }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div x-data="step3Form({{ Js::from([
            'meals'       => $meals,
            'rows'        => $rowsConfig,
            'customerId'  => $currentCustomerId ?? '',
            'defaultMeal' => 'lunch',
            'equipment'   => \Modules\FoodInspection\Support\StorageEquipmentSuggestions::all(),
            'syncUrl'     => route('backend.food-inspection-step3.source-dishes'),
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
                            <span class="label-text font-medium">Đồng bộ từ Bước 2</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Món đã nấu đạt ở Bước 2 + món ăn sẵn/tráng miệng từ Thực đơn</span>
                        </label>
                        <div class="flex flex-wrap items-start gap-2">
                            <div class="w-56">
                                <select id="ts-sync_meal" class="select select-bordered select-sm w-full ts-init" data-ts-placeholder="— Chọn bữa ăn —">
                                    @foreach($meals as $meal)
                                    <option value="{{ $meal['value'] }}" @selected($meal['value'] === 'lunch')>{{ $meal['text'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-outline btn-primary btn-sm gap-1.5" @click="syncSource()" :disabled="syncing">
                                <span x-show="syncing" class="loading loading-spinner loading-xs"></span>
                                <svg x-show="!syncing" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Đồng bộ từ Bước 2
                            </button>
                            <p class="text-xs self-center" :class="syncOk ? 'text-success' : 'text-warning'" x-show="syncMessage" x-text="syncMessage"></p>
                        </div>
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="note"><span class="label-text font-medium">Ghi chú</span><span class="label-text-alt text-base-content/40 text-xs">Tuỳ chọn</span></label>
                        <input type="text" id="note" name="note" maxlength="2000" value="{{ old('note', $isEdit ? $log->note : null) }}"
                               class="input input-bordered input-sm w-full @error('note') input-error @enderror" placeholder="VD: Suất ăn giao đến điểm trường lúc 10h45">
                        @error('note')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Khối 2: lưới kiểm tra trước khi ăn --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-1">Kiểm tra trước khi ăn</h2>
                <p class="text-xs text-base-content/50 mb-4">
                    Món ăn tự điền từ <strong>Sổ Bước 2</strong> (món đã nấu, đạt) và <strong>Thực đơn</strong> (đồ ăn sẵn, tráng miệng chưa qua Bước 2) — chỉ đọc.
                    Giờ bắt đầu ăn không được trước giờ chia xong. Dùng nút <strong>Áp dụng tất cả</strong> ở tiêu đề cột (lấy giá trị dòng 1) hoặc biểu tượng sao chép ở mỗi dòng (chép xuống các dòng dưới).
                </p>

                <div class="overflow-x-auto">
                    <table class="table table-sm min-w-[1500px]">
                        <thead>
                            <tr class="text-xs">
                                <th class="w-10">STT</th><th class="w-40">Ca/bữa ăn</th><th class="min-w-52">Tên món ăn <span class="text-error">*</span></th>
                                <th class="w-28">Nguồn</th><th class="w-24">Số suất</th>
                                <th class="w-44">
                                    Chia xong
                                    <button type="button" class="btn btn-ghost btn-xs px-1 text-primary" @click="applyAll('portion_time')" title="Áp dụng giờ của dòng 1 cho tất cả">Áp dụng tất cả</button>
                                </th>
                                <th class="w-44">
                                    Bắt đầu ăn
                                    <button type="button" class="btn btn-ghost btn-xs px-1 text-primary" @click="applyAll('eat_time')" title="Áp dụng giờ của dòng 1 cho tất cả">Áp dụng tất cả</button>
                                </th>
                                <th class="min-w-64">
                                    Dụng cụ chứa đựng
                                    <button type="button" class="btn btn-ghost btn-xs px-1 text-primary" @click="applyAll('equipment_used')" title="Áp dụng dụng cụ của dòng 1 cho tất cả">Áp dụng tất cả</button>
                                </th>
                                <th class="text-center w-20">Cảm quan</th><th class="min-w-56">Biện pháp xử lý</th><th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in rows" :key="row.n">
                                <tr :class="{ 'bg-error/5': isFailed(row) }">
                                    <td class="text-xs text-base-content/50" x-text="i + 1"></td>
                                    <td>
                                        <input type="hidden" :name="`details[${row.n}][id]`" :value="row.id">
                                        <input type="hidden" :name="`details[${row.n}][step2_detail_id]`" :value="row.step2_detail_id">
                                        <input type="hidden" :name="`details[${row.n}][menu_dish_id]`" :value="row.menu_dish_id">
                                        <input type="hidden" :name="`details[${row.n}][meal_time]`" :value="row.meal_time">
                                        <input type="hidden" :name="`details[${row.n}][sensory_eval]`" :value="row.sensory_eval ? 1 : 0">
                                        <template x-if="locked(row)">
                                            <input type="text" :value="mealLabel(row.meal_time)" readonly class="input input-bordered input-xs w-full text-xs bg-gray-50 text-gray-500 cursor-default">
                                        </template>
                                        <template x-if="!locked(row)">
                                            <select x-model="row.meal_time" x-ts="row.meal_time" class="select select-bordered select-xs w-full">
                                                <template x-for="m in meals" :key="m.value"><option :value="m.value" x-text="m.text"></option></template>
                                            </select>
                                        </template>
                                    </td>
                                    <td>
                                        <input type="text" maxlength="255" :name="`details[${row.n}][dish_name]`" x-model="row.dish_name" :readonly="locked(row)"
                                               data-req="Vui lòng nhập tên món ăn" :data-label="`Tên món ăn (dòng ${i + 1})`"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="locked(row) ? 'bg-gray-50 text-gray-500 cursor-default' : ''"
                                               placeholder="VD: Sữa chua Vinamilk">
                                    </td>
                                    <td class="text-xs">
                                        <span x-show="row.source === 'step2'" class="badge badge-sm badge-soft badge-primary">Từ Bước 2</span>
                                        <span x-show="row.source === 'menu'" class="badge badge-sm badge-soft badge-info">Từ Thực đơn</span>
                                        <span x-show="row.source === ''" class="text-base-content/30">Nhập tay</span>
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="1" :name="`details[${row.n}][quantity]`" x-model="row.quantity" :readonly="locked(row)"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="locked(row) ? 'bg-gray-50 text-gray-500 cursor-default' : ''"
                                               placeholder="VD: 1749">
                                    </td>
                                    <td>
                                        <div class="join w-full">
                                            <input type="time" :name="`details[${row.n}][portion_time]`" x-model="row.portion_time" @dblclick="setNow(row, 'portion_time')"
                                                   class="input input-bordered input-xs join-item w-full min-w-0">
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" @click="setNow(row, 'portion_time')" title="Lấy giờ hiện tại">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                            </button>
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" @click="copyDown(i, 'portion_time')" title="Sao chép giờ này xuống các dòng dưới">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="join w-full">
                                            <input type="time" :name="`details[${row.n}][eat_time]`" x-model="row.eat_time" @dblclick="setNow(row, 'eat_time')"
                                                   :data-label="`Giờ bắt đầu ăn (dòng ${i + 1})`"
                                                   class="input input-bordered input-xs join-item w-full min-w-0" :class="{ 'input-error': timeError(row) }">
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" @click="setNow(row, 'eat_time')" title="Lấy giờ hiện tại">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                            </button>
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" @click="copyDown(i, 'eat_time')" title="Sao chép giờ này xuống các dòng dưới">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                            </button>
                                        </div>
                                        <p class="text-[10px] text-error mt-0.5" x-show="timeError(row)">Không được trước giờ chia xong</p>
                                    </td>
                                    <td>
                                        <div class="join w-full">
                                            <input type="text" list="fi3-equipment" maxlength="255" :name="`details[${row.n}][equipment_used]`" x-model="row.equipment_used"
                                                   class="input input-bordered input-xs join-item w-full min-w-0 placeholder:text-gray-300" placeholder="VD: Khay inox có nắp">
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" @click="copyDown(i, 'equipment_used')" title="Sao chép dụng cụ này xuống các dòng dưới">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-center"><input type="checkbox" class="checkbox checkbox-success checkbox-xs" x-model="row.sensory_eval" @change="onCheckChange(row)"></td>
                                    <td>
                                        <input type="text" maxlength="1000" :name="`details[${row.n}][action_taken]`" x-model="row.action_taken"
                                               :disabled="!isFailed(row)" :data-req="isFailed(row) ? 'Vui lòng ghi biện pháp xử lý' : false"
                                               :data-label="`Biện pháp xử lý (dòng ${i + 1})`" :placeholder="isFailed(row) ? 'VD: Loại bỏ, thay món khác' : '—'"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300">
                                    </td>
                                    <td><button type="button" class="btn btn-ghost btn-xs text-error" @click="removeRow(row)" title="Xóa dòng">✕</button></td>
                                </tr>
                            </template>
                            <tr x-show="rows.length === 0"><td colspan="11" class="text-center text-sm text-base-content/40 py-6">Chưa có dòng nào — chọn khách hàng, ngày, bữa ăn rồi bấm "Đồng bộ từ Bước 2" hoặc "Thêm dòng".</td></tr>
                        </tbody>
                    </table>
                </div>
                <datalist id="fi3-equipment">
                    <template x-for="e in equipment" :key="e"><option :value="e"></option></template>
                </datalist>
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
                            <a href="{{ $isEdit ? route('backend.food-inspection-step3.show', $log) : route('backend.food-inspection-step3.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
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
