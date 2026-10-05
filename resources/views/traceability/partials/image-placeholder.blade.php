{{-- Ảnh placeholder khi sản phẩm chưa có ảnh / ảnh lỗi: logo VISAFO xám nhạt trên nền xám. Biến: $size ('lg' = khung ảnh chính, 'sm' = ô sản phẩm liên quan). --}}
<div class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
    <img src="{{ asset('images/visafo-mark.png') }}" alt="" aria-hidden="true"
         class="{{ ($size ?? 'lg') === 'lg' ? 'h-28 w-28' : 'h-8 w-8' }} object-contain opacity-30 grayscale">
    @if(($size ?? 'lg') === 'lg')
    <span class="mt-3 text-xs font-medium text-gray-400">Hình ảnh sản phẩm đang cập nhật</span>
    @endif
</div>
