{{-- Dải sản phẩm vuốt ngang trên trang truy xuất. Biến: $products (array{name, image, isCurrent}), $icon (từ show.blade.php). --}}
<ul class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-1 [scrollbar-width:none]">
    @foreach($products as $related)
    <li class="w-20 shrink-0 snap-start text-center">
        <div class="relative mx-auto h-16 w-16 overflow-hidden rounded-xl bg-gray-100 {{ $related['isCurrent'] ? 'ring-2 ring-green-600 ring-offset-1' : 'ring-1 ring-black/5' }}">
            @if($related['image'])
            <img src="{{ $related['image'] }}" alt="{{ $related['name'] }}" class="h-full w-full object-cover" loading="lazy">
            @else
            <svg class="m-auto h-full w-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon['box'] }}"/></svg>
            @endif
        </div>
        <p class="mt-1.5 line-clamp-2 text-xs font-medium leading-tight {{ $related['isCurrent'] ? 'text-green-700' : 'text-gray-800' }}">{{ $related['name'] }}</p>
        @if($related['isCurrent'])
        <span class="mt-1 inline-block rounded-full bg-green-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">Bạn đang xem</span>
        @endif
    </li>
    @endforeach
</ul>
