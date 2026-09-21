@php
    $isEdit = isset($log) && $log !== null;

    // Sửa: đổ các mẫu hiện có (kèm id). Có old() (lỗi validate) thì ưu tiên old().
    $initialRows = $isEdit
        ? $log->details->values()->mapWithKeys(fn ($d, $i) => [$i + 1 => [
            'id'              => $d->id,
            'step3_detail_id' => $d->step3_detail_id,
            'menu_dish_id'    => $d->menu_dish_id,
            'source'          => $d->step3_detail_id ? 'step3' : ($d->menu_dish_id ? 'menu' : ''),
            'meal_time'       => $d->meal_time->value,
            'dish_name'       => $d->dish_name,
            'portion_qty'     => $d->portion_qty,
            'sample_volume'   => $d->sample_volume,
            'container_type'  => $d->container_type,
            'storage_temp'    => $d->storage_temp !== null ? rtrim(rtrim((string) $d->storage_temp, '0'), '.') : '',
            'sampled_at'      => $d->sampled_at?->format('Y-m-d H:i:00'),
            'sampler_name'    => $d->sampler_name,
            'destroyed_at'    => $d->destroyed_at?->format('Y-m-d H:i:00'),
            'destroyer_name'  => $d->destroyer_name,
            'quality_note'    => $d->quality_note,
        ]])->all()
        : [];
    $rowsSource = old('details') !== null ? (array) old('details') : $initialRows;
    $rowsConfig = collect($rowsSource)->map(function ($r, $n) {
        $r = (array) $r;
        $r['source'] = $r['source'] ?? (! empty($r['step3_detail_id']) ? 'step3' : (! empty($r['menu_dish_id']) ? 'menu' : ''));

        return ['n' => (int) $n] + $r;
    })->values()->all();

    $currentCustomerId = old('customer_id', $isEdit ? $log->customer_id : null);
    $currentLocation   = old('location_name', $isEdit ? $log->location_name : null);
    $sampleConfig = ['locations' => $locations];
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $isEdit ? 'Hủy mẫu / Chỉnh sửa phiếu lưu mẫu' : 'Lưu mẫu thức ăn' }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Lưu và hủy mẫu thức ăn — Mẫu số 4 &amp; 5, Phụ lục 1 (QĐ 1246/QĐ-BYT)</p>
    </div>
    <a href="{{ $isEdit ? route('backend.food-samples.show', $log) : route('backend.food-samples.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ $isEdit ? route('backend.food-samples.update', $log) : route('backend.food-samples.store') }}" novalidate
      data-food-sample-form data-food-sample-config="{{ json_encode($sampleConfig, JSON_UNESCAPED_UNICODE) }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div x-data="foodSampleForm({{ Js::from([
            'meals'       => $meals,
            'rows'        => $rowsConfig,
            'customerId'  => $currentCustomerId ?? '',
            'defaultMeal' => 'lunch',
            'isEdit'      => $isEdit,
            'userName'    => auth()->user()->name,
            'containers'  => \Modules\FoodInspection\Support\SampleContainerSuggestions::all(),
            'syncUrl'     => route('backend.food-samples.source-dishes'),
        ]) }})" class="space-y-6">

        {{-- Khối 1: thông tin chung + đồng bộ thực đơn --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Thông tin chung</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Người lập phiếu</span>
                            <span class="label-text-alt text-base-content/40 text-xs">{{ $isEdit ? 'Người lập sổ ban đầu' : 'Tự động theo tài khoản' }}</span>
                        </label>
                        <input type="text" value="{{ $isEdit ? $log->creator_name : auth()->user()->name }}" readonly
                               class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="fp-sample_date">
                            <span class="label-text font-medium">Ngày lưu mẫu <span class="text-error">*</span></span>
                        </label>
                        <input type="text" id="fp-sample_date" name="sample_date" data-req="Vui lòng chọn ngày lưu mẫu"
                               value="{{ old('sample_date', $isEdit ? $log->sample_date?->format('Y-m-d') : now()->format('Y-m-d')) }}"
                               class="input input-bordered input-sm w-full fp-init @error('sample_date') input-error @enderror" placeholder="DD/MM/YYYY">
                        @error('sample_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
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
                            <span class="label-text font-medium">Lấy món cần lưu mẫu</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Kế thừa từ Sổ Bước 3 (hoặc Thực đơn) — không gõ tay tên món</span>
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
                                Lấy món cần lưu mẫu
                            </button>
                            <p class="text-xs self-center" :class="syncOk ? 'text-success' : 'text-warning'" x-show="syncMessage" x-text="syncMessage"></p>
                        </div>
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="note"><span class="label-text font-medium">Ghi chú</span><span class="label-text-alt text-base-content/40 text-xs">Tuỳ chọn</span></label>
                        <input type="text" id="note" name="note" maxlength="2000" value="{{ old('note', $isEdit ? $log->note : null) }}"
                               class="input input-bordered input-sm w-full @error('note') input-error @enderror" placeholder="VD: Lưu mẫu ca trưa">
                        @error('note')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Khối 2: lưới lưu mẫu (nhịp 1) + hủy mẫu (nhịp 2) --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-1">Mẫu thức ăn lưu</h2>
                <p class="text-xs text-base-content/50 mb-2">
                    <strong>Nhịp 1 — Lấy mẫu:</strong> tên món kế thừa (chỉ đọc); khối lượng gợi ý 100g (đặc) / 150ml (lỏng), nhiệt độ mặc định 4°C (chuẩn 2–8°C), lưu trong dụng cụ có nắp đậy kín.
                    <strong>Nhịp 2 — Hủy mẫu:</strong> các cột Hủy mẫu bị khóa cho đến khi đủ <strong>24 giờ</strong> kể từ lúc lấy mẫu (mở phiếu ở chế độ sửa); mẫu đã hủy không thể sửa.
                </p>
                <p class="text-xs mb-3 text-warning" x-show="isEdit && pendingCount > 0">
                    Có <strong x-text="pendingCount"></strong> mẫu đã đủ 24 giờ — có thể ghi nhận hủy mẫu.
                </p>

                <div class="overflow-x-auto">
                    <table class="table table-sm min-w-[2380px]">
                        <thead>
                            <tr class="text-xs">
                                <th colspan="6" class="text-center bg-base-200/60 border-b border-base-300">Mẫu thức ăn</th>
                                <th colspan="5" class="text-center bg-primary/10 border-b border-base-300">Nhịp 1 — Lấy mẫu</th>
                                <th colspan="4" class="text-center bg-warning/10 border-b border-base-300">Nhịp 2 — Hủy mẫu (sau 24 giờ)</th>
                            </tr>
                            <tr class="text-xs">
                                <th class="w-10">STT</th><th class="w-40">Bữa ăn</th><th class="min-w-52">Tên mẫu thức ăn <span class="text-error">*</span></th>
                                <th class="w-28">Nguồn</th><th class="w-24">Số suất</th><th class="w-28">Khối lượng <span class="text-error">*</span></th>
                                <th class="min-w-52">
                                    Dụng cụ
                                    <button type="button" class="btn btn-ghost btn-xs px-1 text-primary" @click="applyAll('container_type')" title="Áp dụng dụng cụ của dòng 1 cho tất cả">Áp dụng tất cả</button>
                                </th>
                                <th class="w-36">
                                    Nhiệt độ (°C)
                                    <button type="button" class="btn btn-ghost btn-xs px-1 text-primary" @click="applyAll('storage_temp')" title="Áp dụng nhiệt độ của dòng 1 cho tất cả">Áp dụng tất cả</button>
                                </th>
                                <th class="w-56">Thời gian lấy mẫu <span class="text-error">*</span></th>
                                <th class="min-w-48">
                                    Người lấy mẫu <span class="text-error">*</span>
                                    <button type="button" class="btn btn-ghost btn-xs px-1 text-primary" @click="applyAll('sampler_name')" title="Áp dụng người lấy mẫu của dòng 1 cho tất cả">Áp dụng tất cả</button>
                                </th>
                                <th class="w-56">Thời gian hủy mẫu</th><th class="min-w-44">Người hủy mẫu</th><th class="min-w-48">Chất lượng khi hủy</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in rows" :key="row.n">
                                <tr :class="{ 'bg-base-200/40': row.done, 'bg-warning/5': !row.done && canDestroy(row) }">
                                    <td class="text-xs text-base-content/50" x-text="i + 1"></td>
                                    <td>
                                        <input type="hidden" :name="`details[${row.n}][id]`" :value="row.id">
                                        <input type="hidden" :name="`details[${row.n}][step3_detail_id]`" :value="row.step3_detail_id">
                                        <input type="hidden" :name="`details[${row.n}][menu_dish_id]`" :value="row.menu_dish_id">
                                        <input type="hidden" :name="`details[${row.n}][meal_time]`" :value="row.meal_time">
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
                                        <input type="text" maxlength="255" :name="`details[${row.n}][dish_name]`" x-model="row.dish_name" :readonly="locked(row)" @change="onDishChange(row)"
                                               data-req="Vui lòng nhập tên mẫu thức ăn" :data-label="`Tên mẫu thức ăn (dòng ${i + 1})`"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="locked(row) ? 'bg-gray-50 text-gray-500 cursor-default' : ''"
                                               placeholder="VD: Canh rau cải">
                                    </td>
                                    <td class="text-xs">
                                        <span x-show="row.source === 'step3'" class="badge badge-sm badge-soft badge-primary">Từ Bước 3</span>
                                        <span x-show="row.source === 'menu'" class="badge badge-sm badge-soft badge-info">Từ Thực đơn</span>
                                        <span x-show="row.source === ''" class="text-base-content/30">Nhập tay</span>
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="1" :name="`details[${row.n}][portion_qty]`" x-model="row.portion_qty" :readonly="locked(row)"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="locked(row) ? 'bg-gray-50 text-gray-500 cursor-default' : ''"
                                               placeholder="VD: 1749">
                                    </td>
                                    <td>
                                        <input type="text" maxlength="20" :name="`details[${row.n}][sample_volume]`" x-model="row.sample_volume" @input="row.volumeTouched = true" :readonly="row.done"
                                               data-req="Vui lòng nhập khối lượng/thể tích mẫu" :data-label="`Khối lượng mẫu (dòng ${i + 1})`"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" :class="{ 'input-warning': volumeTooSmall(row), 'bg-gray-50 text-gray-500 cursor-default': row.done }"
                                               placeholder="100g / 150ml">
                                        <p class="text-[10px] text-warning mt-0.5" x-show="volumeTooSmall(row)">Tối thiểu 100g / 150ml</p>
                                    </td>
                                    <td>
                                        <div class="join w-full">
                                            <input type="text" list="fs-containers" maxlength="100" :name="`details[${row.n}][container_type]`" x-model="row.container_type" :readonly="row.done"
                                                   class="input input-bordered input-xs join-item w-full min-w-0 placeholder:text-gray-300" :class="row.done ? 'bg-gray-50 text-gray-500 cursor-default' : ''" placeholder="VD: Hộp inox có nắp">
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" :disabled="row.done" @click="copyDown(i, 'container_type')" title="Sao chép dụng cụ này xuống các dòng dưới">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="join w-full">
                                            <input type="number" step="0.1" :name="`details[${row.n}][storage_temp]`" x-model="row.storage_temp" :readonly="row.done"
                                                   class="input input-bordered input-xs join-item w-full min-w-0" :class="{ 'input-warning': tempOutOfRange(row), 'bg-gray-50 text-gray-500 cursor-default': row.done }">
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" :disabled="row.done" @click="copyDown(i, 'storage_temp')" title="Sao chép nhiệt độ này xuống các dòng dưới">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                            </button>
                                        </div>
                                        <p class="text-[10px] text-warning mt-0.5" x-show="tempOutOfRange(row)">Ngoài chuẩn 2–8°C</p>
                                    </td>
                                    <td>
                                        <div class="join w-full" :class="row.done ? 'pointer-events-none opacity-60' : ''">
                                            <input type="text" :name="`details[${row.n}][sampled_at]`" x-model="row.sampled_at" x-fp.datetime="row.sampled_at"
                                                   data-req="Vui lòng nhập thời gian lấy mẫu" :data-label="`Thời gian lấy mẫu (dòng ${i + 1})`"
                                                   class="input input-bordered input-xs join-item w-full min-w-0" placeholder="DD/MM/YYYY HH:mm">
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" @click="setSampledNow(row)" title="Lấy giờ hiện tại">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="join w-full">
                                            <input type="text" maxlength="255" :name="`details[${row.n}][sampler_name]`" x-model="row.sampler_name" :readonly="row.done"
                                                   data-req="Vui lòng nhập người lấy mẫu" :data-label="`Người lấy mẫu (dòng ${i + 1})`"
                                                   class="input input-bordered input-xs join-item w-full min-w-0" :class="row.done ? 'bg-gray-50 text-gray-500 cursor-default' : ''">
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" :disabled="row.done" @click="copyDown(i, 'sampler_name')" title="Sao chép người lấy mẫu xuống các dòng dưới">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                            </button>
                                        </div>
                                    </td>

                                    {{-- Nhịp 2 — Hủy mẫu: khóa (disabled → không gửi lên) cho đến khi đủ 24 giờ --}}
                                    <td>
                                        <div class="join w-full" :class="(row.done || !canDestroy(row)) ? 'pointer-events-none opacity-50' : ''">
                                            <input type="text" :name="`details[${row.n}][destroyed_at]`" x-model="row.destroyed_at" x-fp.datetime="row.destroyed_at" @input="onDestroyedChange(row)"
                                                   :disabled="!row.done && !canDestroy(row)" :data-label="`Thời gian hủy mẫu (dòng ${i + 1})`"
                                                   class="input input-bordered input-xs join-item w-full min-w-0" placeholder="DD/MM/YYYY HH:mm">
                                            <button type="button" class="btn btn-xs join-item btn-ghost border border-base-300" @click="setDestroyNow(row)" title="Lấy giờ hiện tại">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                            </button>
                                        </div>
                                        <p class="text-[10px] text-warning mt-0.5" x-show="!row.done && !canDestroy(row)"
                                           x-text="isEdit ? remainingLabel(row) : 'Chỉ hủy được sau 24 giờ lưu mẫu (mở lại phiếu để hủy)'"></p>
                                    </td>
                                    <td>
                                        <input type="text" maxlength="255" :name="`details[${row.n}][destroyer_name]`" x-model="row.destroyer_name"
                                               :disabled="!row.done && !row.destroyed_at" :readonly="row.done"
                                               class="input input-bordered input-xs w-full" :class="row.done ? 'bg-gray-50 text-gray-500 cursor-default' : ''" placeholder="—">
                                    </td>
                                    <td>
                                        <input type="text" maxlength="255" :name="`details[${row.n}][quality_note]`" x-model="row.quality_note"
                                               :disabled="!row.done && !row.destroyed_at" :readonly="row.done"
                                               class="input input-bordered input-xs w-full" :class="row.done ? 'bg-gray-50 text-gray-500 cursor-default' : ''" placeholder="—">
                                    </td>
                                    <td><button type="button" class="btn btn-ghost btn-xs text-error" :disabled="row.done" @click="removeRow(row)" title="Xóa dòng">✕</button></td>
                                </tr>
                            </template>
                            <tr x-show="rows.length === 0"><td colspan="15" class="text-center text-sm text-base-content/40 py-6">Chưa có mẫu nào — chọn khách hàng, ngày, bữa ăn rồi bấm "Lấy món cần lưu mẫu" hoặc "Thêm dòng".</td></tr>
                        </tbody>
                    </table>
                </div>
                <datalist id="fs-containers">
                    <template x-for="c in containers" :key="c"><option :value="c"></option></template>
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
                            <span><span class="text-base-content/50">Số mẫu:</span> <span class="font-mono" x-text="rows.length"></span></span>
                            <span><span class="text-base-content/50">Đã hủy:</span> <span class="font-mono" x-text="destroyedCount"></span></span>
                            <span x-show="isEdit"><span class="text-base-content/50">Chờ hủy:</span> <span class="font-mono" :class="pendingCount > 0 ? 'text-warning font-semibold' : ''" x-text="pendingCount"></span></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-xs text-base-content/30"><span class="text-error">*</span> là trường bắt buộc</p>
                        <div class="flex gap-2">
                            <a href="{{ $isEdit ? route('backend.food-samples.show', $log) : route('backend.food-samples.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                            <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="submitting">
                                <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                                <span x-text="submitting ? 'Đang xử lý...' : {{ Js::from($isEdit ? 'Lưu lại' : 'Lưu mẫu') }}"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
