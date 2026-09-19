@php($t = $template ?? null)

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div class="card w-full bg-base-100 shadow-sm border border-base-200">
    <div class="card-body">

        <h2 class="card-title text-base mb-5">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>
            </svg>
            Thông tin mẫu tem
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div class="form-control sm:col-span-2">
                <label class="label py-0 pb-1.5" for="lt-name">
                    <span class="label-text font-medium">Tên mẫu tem <span class="text-error">*</span></span>
                </label>
                <input id="lt-name" type="text" name="name" value="{{ old('name', $t?->name) }}" maxlength="255" autofocus
                       class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                       placeholder="VD: Tem Thực Phẩm Chức Năng 60x40">
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control sm:col-span-2">
                <label class="label py-0 pb-1.5" for="lt-view-path">
                    <span class="label-text font-medium">View Path <span class="text-error">*</span></span>
                    <span class="label-text-alt text-base-content/40 text-xs">Blade view, bắt đầu bằng "labels."</span>
                </label>
                <input id="lt-view-path" type="text" name="view_path" value="{{ old('view_path', $t?->view_path) }}" maxlength="255"
                       pattern="labels(\.[A-Za-z0-9_\-]+)+"
                       class="input input-bordered input-sm w-full font-mono @error('view_path') input-error @enderror"
                       placeholder="labels.templates.functional_food">
                <p class="mt-1 text-xs text-base-content/40">
                    File tương ứng: <code class="bg-base-200 px-1 rounded">resources/views/labels/templates/functional_food.blade.php</code>.
                    Chỉ dùng chữ, số, <code class="bg-base-200 px-1 rounded">_</code>, <code class="bg-base-200 px-1 rounded">-</code> và dấu chấm.
                </p>
                @error('view_path')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5" for="lt-size">
                    <span class="label-text font-medium">Kích thước mặc định</span>
                    <span class="label-text-alt text-base-content/40 text-xs">Tuỳ chọn</span>
                </label>
                <input id="lt-size" type="text" name="default_size" value="{{ old('default_size', $t?->default_size) }}" maxlength="50"
                       class="input input-bordered input-sm w-full @error('default_size') input-error @enderror"
                       placeholder="VD: 60x40 mm">
                @error('default_size')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

        </div>

        <div class="form-control mt-4">
            <label class="label py-0 pb-1.5" for="lt-description">
                <span class="label-text font-medium">Mô tả</span>
                <span class="label-text-alt text-base-content/40 text-xs">Ghi chú các attribute động mẫu hỗ trợ</span>
            </label>
            <textarea id="lt-description" name="description" rows="4" maxlength="2000"
                      class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                      placeholder="VD: Hỗ trợ attribute HDSD, Liều dùng, Bảo quản. Biến truyền vào view: $item, $log, $attributes.">{{ old('description', $t?->description) }}</textarea>
            @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center gap-3 pt-4 mt-4 border-t border-base-200">
            <div class="ml-auto flex gap-2">
                <a href="{{ route('backend.label-templates.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                <button type="submit" class="btn btn-primary btn-sm gap-1.5">{{ $t ? 'Lưu thay đổi' : 'Tạo mẫu tem' }}</button>
            </div>
        </div>

    </div>
</div>
