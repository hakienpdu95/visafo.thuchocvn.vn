@php
    $isEdit = isset($menu) && $menu !== null;
    $initialDishes = $isEdit
        ? $menu->dishes->values()->mapWithKeys(fn ($d, $i) => [$i + 1 => [
            'id' => $d->id, 'dish_name' => $d->dish_name, 'main_ingredients' => $d->main_ingredients, 'servings' => $d->servings,
        ]])->all()
        : [];
    $dishesSource = old('dishes') !== null ? (array) old('dishes') : $initialDishes;
    $dishesConfig = collect($dishesSource)->map(fn ($d, $n) => ['n' => (int) $n] + (array) $d)->values()->all();
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $isEdit ? 'Chỉnh sửa thực đơn' : 'Tạo thực đơn' }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Thực đơn theo ngày và bữa ăn của từng cơ sở — là nguồn dữ liệu cho Sổ kiểm thực Bước 2</p>
    </div>
    <a href="{{ route('backend.menus.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ $isEdit ? route('backend.menus.update', $menu) : route('backend.menus.store') }}" novalidate data-menu-form>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div x-data="menuDishes({{ Js::from(['dishes' => $dishesConfig]) }})" class="space-y-6">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Thông tin thực đơn</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="ts-customer_id">
                            <span class="label-text font-medium">Cơ sở / Doanh nghiệp suất ăn <span class="text-error">*</span></span>
                            <span class="label-text-alt text-base-content/40 text-xs">Lấy từ Khách hàng</span>
                        </label>
                        <select id="ts-customer_id" name="customer_id" data-req="Vui lòng chọn cơ sở / doanh nghiệp suất ăn"
                                class="select select-bordered select-sm w-full ts-init @error('customer_id') select-error @enderror"
                                data-ts-placeholder="— Chọn cơ sở —">
                            <option value="">— Chọn cơ sở —</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer['value'] }}" @selected(old('customer_id', $isEdit ? $menu->customer_id : null) === $customer['value'])>{{ $customer['text'] }}</option>
                            @endforeach
                            @if($isEdit && collect($customers)->doesntContain('value', $menu->customer_id))
                            <option value="{{ $menu->customer_id }}" selected>{{ $menu->customer?->name }}</option>
                            @endif
                        </select>
                        @error('customer_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="fp-menu_date">
                            <span class="label-text font-medium">Ngày áp dụng <span class="text-error">*</span></span>
                        </label>
                        <input type="text" id="fp-menu_date" name="menu_date" data-req="Vui lòng chọn ngày áp dụng"
                               value="{{ old('menu_date', $isEdit ? $menu->menu_date?->format('Y-m-d') : now()->format('Y-m-d')) }}"
                               class="input input-bordered input-sm w-full fp-init @error('menu_date') input-error @enderror" placeholder="DD/MM/YYYY">
                        @error('menu_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="ts-meal_time">
                            <span class="label-text font-medium">Bữa ăn <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-meal_time" name="meal_time" data-req="Vui lòng chọn bữa ăn"
                                class="select select-bordered select-sm w-full ts-init @error('meal_time') select-error @enderror"
                                data-ts-placeholder="— Chọn bữa ăn —">
                            <option value="">— Chọn bữa ăn —</option>
                            @foreach($meals as $meal)
                            <option value="{{ $meal['value'] }}" @selected(old('meal_time', $isEdit ? $menu->meal_time->value : 'lunch') === $meal['value'])>{{ $meal['text'] }}</option>
                            @endforeach
                        </select>
                        @error('meal_time')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="note"><span class="label-text font-medium">Ghi chú</span><span class="label-text-alt text-base-content/40 text-xs">Tuỳ chọn</span></label>
                        <input type="text" id="note" name="note" maxlength="2000" value="{{ old('note', $isEdit ? $menu->note : null) }}"
                               class="input input-bordered input-sm w-full @error('note') input-error @enderror" placeholder="VD: Thực đơn tuần 39">
                        @error('note')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Danh sách món ăn</h2>

                <div class="overflow-x-auto">
                    <table class="table table-sm min-w-[820px]">
                        <thead>
                            <tr class="text-xs">
                                <th class="w-10">STT</th><th class="min-w-56">Tên món ăn <span class="text-error">*</span></th>
                                <th class="min-w-72">Nguyên liệu chính</th><th class="w-32">Số suất ăn</th><th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(d, i) in dishes" :key="d.n">
                                <tr>
                                    <td class="text-xs text-base-content/50" x-text="i + 1"></td>
                                    <td>
                                        <input type="hidden" :name="`dishes[${d.n}][id]`" :value="d.id">
                                        <input type="text" maxlength="255" :name="`dishes[${d.n}][dish_name]`" x-model="d.dish_name"
                                               data-req="Vui lòng nhập tên món ăn" :data-label="`Tên món ăn (dòng ${i + 1})`"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" placeholder="VD: Thịt xào su su">
                                    </td>
                                    <td><input type="text" maxlength="500" :name="`dishes[${d.n}][main_ingredients]`" x-model="d.main_ingredients"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" placeholder="VD: Thịt lợn, su su"></td>
                                    <td><input type="number" min="0" step="1" :name="`dishes[${d.n}][servings]`" x-model="d.servings"
                                               class="input input-bordered input-xs w-full placeholder:text-gray-300" placeholder="VD: 1740"></td>
                                    <td><button type="button" class="btn btn-ghost btn-xs text-error" @click="removeDish(d)" title="Xóa dòng">✕</button></td>
                                </tr>
                            </template>
                            <tr x-show="dishes.length === 0"><td colspan="5" class="text-center text-sm text-base-content/40 py-6">Chưa có món nào — bấm "Thêm món".</td></tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-ghost btn-sm gap-1.5 text-primary self-start mt-2" @click="addDish()">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Thêm món
                </button>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Tóm tắt</p>
                        <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs">
                            <span><span class="text-base-content/50">Số món:</span> <span class="font-mono" x-text="dishes.length"></span></span>
                            <span><span class="text-base-content/50">Tổng số suất:</span> <span class="font-mono" x-text="totalServings"></span></span>
                            @if($isEdit)<span class="text-base-content/40">Sửa lần cuối {{ $menu->updated_at?->diffForHumans() }}</span>@endif
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-xs text-base-content/30"><span class="text-error">*</span> là trường bắt buộc</p>
                        <div class="flex gap-2">
                            <a href="{{ route('backend.menus.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                            <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="submitting">
                                <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                                <span x-text="submitting ? 'Đang xử lý...' : {{ Js::from($isEdit ? 'Lưu lại' : 'Tạo thực đơn') }}"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
