{{-- Ảnh sản phẩm: upload nhiều ảnh + chọn ảnh chính (ảnh chính = slide đầu tiên ngoài trang truy xuất). Cần $galleryItems. --}}
<div class="product-gallery card bg-base-100 shadow-sm border border-base-200"
     x-data="productGallery({ items: @js($galleryItems) })">
    <div class="card-body">

        <h2 class="card-title text-base mb-1">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Hình ảnh sản phẩm
        </h2>
        <p class="text-xs text-base-content/50 mb-3">JPG, PNG, WEBP · tối đa 10MB/ảnh. Ảnh chính sẽ hiển thị đầu tiên trên trang truy xuất nguồn gốc.</p>

        <input type="file" x-ref="pond" multiple accept="image/jpeg,image/png,image/webp">

        <input type="hidden" name="gallery" :value="galleryJson">
        <input type="hidden" name="main_image" :value="mainId ?? ''">

        {{-- Cùng số cột với lưới item FilePond (.product-gallery trong product.scss + previewItemWidth() trong product-form.js): 2 → 5 → 8 → 10.
             Ô hẹp (< 120px) tự chuyển sang dạng gọn qua container query @container/tile: badge thành ★, ẩn nhãn radio. --}}
        <template x-if="items.length">
            <div class="grid grid-cols-2 sm:grid-cols-5 lg:grid-cols-8 xl:grid-cols-10 gap-1.5 mt-3">
                <template x-for="(item, index) in items" :key="item.id">
                    <div class="@container/tile group relative min-w-0 rounded-md overflow-hidden border-2 bg-base-100 transition"
                         :class="mainId === item.id ? 'border-primary ring-2 ring-primary/25' : 'border-base-200 hover:border-base-300'">

                        <div class="relative aspect-square bg-base-200">
                            <button type="button" class="block w-full h-full" @click="setMain(item.id)" title="Chọn làm ảnh chính">
                                <img :src="item.thumb_url" alt="" class="w-full h-full object-cover" loading="lazy">
                            </button>

                            <span x-show="mainId === item.id" title="Ảnh chính"
                                  class="absolute top-0.5 left-0.5 badge badge-primary badge-xs h-4 px-1 text-[10px] font-semibold shadow pointer-events-none">
                                <span class="@max-[120px]/tile:hidden">Ảnh chính</span>
                                <span class="hidden @max-[120px]/tile:inline">★</span>
                            </span>

                            <button type="button" @click="remove(item)" title="Xóa ảnh"
                                    class="absolute top-0.5 right-0.5 btn btn-circle btn-error min-h-0 w-4 h-4 p-0 opacity-80 hover:opacity-100">
                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            {{-- Đổi thứ tự: luôn hiện trên cảm ứng, chỉ hiện khi hover trên desktop --}}
                            <div x-show="items.length > 1"
                                 class="absolute bottom-0.5 inset-x-0.5 flex justify-between transition sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100">
                                <button type="button" class="btn btn-square min-h-0 w-4 h-4 p-0 text-xs leading-none bg-base-100/90 border-0 shadow-sm" title="Đưa lên trước"
                                        :class="index === 0 && 'invisible'" @click="move(index, -1)">‹</button>
                                <button type="button" class="btn btn-square min-h-0 w-4 h-4 p-0 text-xs leading-none bg-base-100/90 border-0 shadow-sm" title="Đưa ra sau"
                                        :class="index === items.length - 1 && 'invisible'" @click="move(index, 1)">›</button>
                            </div>
                        </div>

                        <label class="flex items-center justify-center @min-[120px]/tile:justify-start gap-1 px-1 py-0.5 border-t border-base-200 cursor-pointer min-w-0"
                               title="Chọn làm ảnh chính">
                            <input type="radio" class="radio radio-primary radio-xs shrink-0 w-3.5 h-3.5"
                                   :value="item.id" :checked="mainId === item.id" @change="setMain(item.id)">
                            <span class="@max-[120px]/tile:hidden text-[11px] leading-tight truncate"
                                  :class="mainId === item.id ? 'text-primary font-medium' : 'text-base-content/70'">Chọn làm ảnh chính</span>
                        </label>
                    </div>
                </template>
            </div>
        </template>

        <p x-show="!items.length" class="text-xs text-base-content/40 mt-2">Chưa có ảnh nào.</p>
        @error('gallery')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>
</div>
