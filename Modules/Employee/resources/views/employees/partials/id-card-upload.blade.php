@php($url = $employee?->getMediaUrl($field))
<div class="form-control" x-data="{ remove: false, preview: null }">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium">{{ $label }}</span>
        <span class="label-text-alt text-xs text-base-content/40">JPG/PNG/WEBP, tối đa 10MB</span>
    </label>

    @if($url)
    <div class="relative mb-2 w-full aspect-[1.6] rounded-lg border border-base-200 bg-base-200/40 overflow-hidden" x-show="!preview">
        <a href="{{ $url }}" target="_blank" rel="noopener" :class="remove ? 'opacity-30 pointer-events-none' : ''">
            <img src="{{ $url }}" alt="{{ $label }}" class="w-full h-full object-contain">
        </a>
        <button type="button" @click="remove = !remove"
                class="btn btn-xs btn-circle absolute top-1.5 right-1.5 shadow"
                :class="remove ? 'btn-neutral' : 'btn-error'"
                :title="remove ? 'Hoàn tác' : 'Xóa ảnh hiện tại'">
            <svg x-show="!remove" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            <svg x-show="remove" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a5 5 0 015 5v2M3 10l5-5m-5 5l5 5"/></svg>
        </button>
        <p x-show="remove" class="absolute inset-x-0 bottom-0 bg-error/90 text-error-content text-xs text-center py-1">Ảnh sẽ bị xóa khi lưu</p>
    </div>
    <input type="hidden" name="remove_{{ $field }}" :value="remove ? 1 : 0" value="0">
    @endif

    <template x-if="preview">
        <img :src="preview" alt="{{ $label }}" class="mb-2 w-full aspect-[1.6] rounded-lg border border-base-200 object-contain bg-base-200/40">
    </template>

    <input type="file" name="{{ $field }}" accept="image/jpeg,image/png,image/webp"
           @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null; if (preview) remove = false"
           class="file-input file-input-bordered file-input-sm w-full @error($field) file-input-error @enderror">
    @error($field)<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
</div>
