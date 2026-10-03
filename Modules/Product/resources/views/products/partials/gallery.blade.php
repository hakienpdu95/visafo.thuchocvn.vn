{{-- Ảnh sản phẩm: upload nhiều ảnh + chọn ảnh chính (ảnh chính = slide đầu tiên ngoài trang truy xuất). Cần $galleryItems. --}}
<div class="card bg-base-100 shadow-sm border border-base-200"
     x-data="productGallery({ items: @js($galleryItems) })">
    <div class="card-body">

        <h2 class="card-title text-base mb-1">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Hình ảnh sản phẩm
        </h2>
        <p class="text-xs text-base-content/50 mb-4">JPG, PNG, WEBP · tối đa 10MB/ảnh. Ảnh chính sẽ hiển thị đầu tiên trên trang truy xuất nguồn gốc.</p>

        <input type="file" x-ref="pond" multiple accept="image/jpeg,image/png,image/webp">

        <input type="hidden" name="gallery" :value="galleryJson">
        <input type="hidden" name="main_image" :value="mainId ?? ''">

        <template x-if="items.length">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mt-4">
                <template x-for="(item, index) in items" :key="item.id">
                    <div class="group relative rounded-lg overflow-hidden border-2 bg-base-200 transition"
                         :class="mainId === item.id ? 'border-primary ring-2 ring-primary/30' : 'border-base-200 hover:border-base-300'">

                        <button type="button" class="block w-full aspect-square" @click="setMain(item.id)" title="Chọn làm ảnh chính">
                            <img :src="item.thumb_url" alt="" class="w-full h-full object-cover" loading="lazy">
                        </button>

                        <span x-show="mainId === item.id"
                              class="absolute top-1.5 left-1.5 badge badge-primary badge-sm font-medium shadow">Ảnh chính</span>

                        <button type="button" @click="remove(item)" title="Xóa ảnh"
                                class="absolute top-1.5 right-1.5 btn btn-circle btn-xs btn-error opacity-80 hover:opacity-100">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>

                        <div class="flex items-center justify-between gap-1 px-2 py-1.5 bg-base-100 border-t border-base-200">
                            <label class="flex items-center gap-1.5 cursor-pointer min-w-0">
                                <input type="radio" class="radio radio-primary radio-xs"
                                       :value="item.id" :checked="mainId === item.id" @change="setMain(item.id)">
                                <span class="text-xs truncate">Chọn làm ảnh chính</span>
                            </label>
                            <div class="flex shrink-0">
                                <button type="button" class="btn btn-ghost btn-xs btn-square" title="Đưa lên trước"
                                        :disabled="index === 0" @click="move(index, -1)">‹</button>
                                <button type="button" class="btn btn-ghost btn-xs btn-square" title="Đưa ra sau"
                                        :disabled="index === items.length - 1" @click="move(index, 1)">›</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <p x-show="!items.length" class="text-xs text-base-content/40 mt-2">Chưa có ảnh nào.</p>
        @error('gallery')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>
</div>
