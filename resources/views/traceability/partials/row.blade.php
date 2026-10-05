{{-- Một dòng thông tin có icon trên trang truy xuất. Biến: $ic (key trong $icon của show.blade.php), $label, $value (rỗng → không render dòng),
     tùy chọn $sub, $mono, $accent, $danger, $badge. --}}
@if(filled($value))
<li class="flex gap-3 py-3">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ ($danger ?? false) ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700' }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon[$ic] }}"/></svg>
    </span>
    <div class="min-w-0 flex-1">
        <p class="text-xs text-gray-500">{{ $label }}</p>
        <p class="break-words font-medium text-sm leading-snug
                  {{ ($mono ?? false) ? 'font-mono' : '' }}
                  {{ ($danger ?? false) ? 'text-red-600' : (($accent ?? false) ? 'text-green-700' : 'text-gray-900') }}">
            {{ $value }}
            @if(!empty($badge))<span class="ml-1 rounded bg-red-100 px-1.5 py-0.5 align-middle font-sans text-xs font-medium text-sm text-red-600">{{ $badge }}</span>@endif
        </p>
        @if(!empty($sub))<p class="mt-0.5 break-words text-sm text-gray-600">{{ $sub }}</p>@endif
    </div>
</li>
@endif
