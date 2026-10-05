{{-- Tab "Đánh giá" trên trang truy xuất. Biến: $trace, $icon (từ show.blade.php).
     Hai form gửi Ajax tới route trace.reviews.store (JS ở show.blade.php, data-trace-review-form); mọi bản gửi gắn mã TXNG qua input ẩn. --}}
@php
    $scoreOptions = [5 => '5 - Rất tốt', 4 => '4 - Tốt', 3 => '3 - Bình thường', 2 => '2 - Chưa tốt', 1 => '1 - Không đạt'];
    $summary = $trace->reviewSummary;
    $stars = fn (?float $v) => $v === null ? '' : str_repeat('★', (int) round($v)) . str_repeat('☆', 5 - (int) round($v));
@endphp

{{-- 1. Điểm chất lượng tổng (đánh giá đã duyệt của sản phẩm) --}}
<section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-green-700">
        <span class="h-4 w-1 rounded bg-green-600"></span> Điểm chất lượng
    </h3>
    @if($summary['count'] > 0)
    <div class="flex items-center gap-4">
        <div class="shrink-0 text-center">
            <p class="text-4xl font-extrabold leading-none text-green-700">{{ number_format($summary['average'], 1, ',', '') }}<span class="text-base font-semibold text-gray-400"> / 5</span></p>
            <p class="mt-1 text-amber-500" aria-hidden="true">{{ $stars($summary['average']) }}</p>
            <p class="text-xs text-gray-500">{{ $summary['count'] }} đánh giá đã xác minh</p>
        </div>
        <dl class="min-w-0 flex-1 space-y-1.5 text-sm">
            @foreach($summary['criteria'] as $label => $avg)
            <div>
                <div class="flex justify-between gap-2"><dt class="truncate text-gray-600">{{ $label }}</dt><dd class="font-semibold text-gray-900">{{ number_format($avg, 1, ',', '') }}</dd></div>
                <div class="mt-0.5 h-1.5 rounded-full bg-gray-100"><div class="h-1.5 rounded-full bg-green-500" style="width: {{ $avg / 5 * 100 }}%"></div></div>
            </div>
            @endforeach
        </dl>
    </div>
    @else
    <p class="text-sm text-gray-500">Sản phẩm chưa có đánh giá đã xác minh. Hãy là người đầu tiên đánh giá lô hàng này!</p>
    @endif
</section>

{{-- 2. Form đánh giá lô hàng --}}
<section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-green-700">
        <span class="h-4 w-1 rounded bg-green-600"></span> Đánh giá lô hàng này
    </h3>
    <form data-trace-review-form action="{{ route('trace.reviews.store') }}" method="POST" novalidate class="space-y-3">
        <input type="hidden" name="trace_code" value="{{ $trace->traceCode }}">
        <input type="hidden" name="type" value="rating">
        {{-- honeypot chống bot — người dùng không thấy --}}
        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
        <div class="grid grid-cols-1 gap-3 min-[400px]:grid-cols-2">
            @foreach(\Modules\SalesOrder\Models\TraceReview::SCORES as $field => $label)
            <label class="block text-sm">
                <span class="mb-1 block font-medium text-gray-700">{{ $label }} <span class="text-red-500">*</span></span>
                <select name="{{ $field }}" required class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm focus:border-green-600">
                    <option value="">— Chọn điểm —</option>
                    @foreach($scoreOptions as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
                </select>
            </label>
            @endforeach
        </div>
        <label class="block text-sm">
            <span class="mb-1 block font-medium text-gray-700">Nhận xét</span>
            <textarea name="comment" rows="3" maxlength="2000" placeholder="Chia sẻ cảm nhận về sản phẩm, đóng gói, giao hàng…"
                      class="w-full rounded-lg border border-gray-300 px-2.5 py-2 text-sm focus:border-green-600"></textarea>
        </label>
        <label class="flex items-start gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_public_requested" value="1" class="mt-0.5 h-4 w-4 accent-green-600">
            <span>Cho phép {{ $trace->brand }} công khai nhận xét này trên trang truy xuất (tên đơn vị nhận hàng được che bớt).</span>
        </label>
        <button type="submit" class="w-full rounded-full bg-green-600 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700 disabled:opacity-70">Gửi đánh giá</button>
    </form>
</section>

{{-- 3. Nhận xét đã xác minh --}}
@if($trace->reviews)
<section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
    <h3 class="mb-1 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-green-700">
        <span class="h-4 w-1 rounded bg-green-600"></span> Nhận xét từ khách hàng
    </h3>
    <ul class="divide-y divide-gray-100">
        @foreach($trace->reviews as $review)
        <li class="py-3">
            <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                <p class="text-sm font-semibold text-gray-900">{{ $review['name'] ?? 'Khách hàng' }}</p>
                @if($review['score'] !== null)<span class="text-sm text-amber-500" title="{{ $review['score'] }}/5">{{ $stars($review['score']) }}</span>@endif
            </div>
            @if($review['verified'])
            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-semibold text-green-700 ring-1 ring-green-200">✓ Đã xác minh giao dịch</span>
            @endif
            @if($review['comment'])<p class="mt-1.5 whitespace-pre-line text-sm text-gray-700">{{ $review['comment'] }}</p>@endif
            <p class="mt-1 text-xs text-gray-400">{{ implode(' • ', array_filter([$review['lot'], $review['at'] ? 'đánh giá ' . $review['at']->format('d/m/Y') : null])) }}</p>
        </li>
        @endforeach
    </ul>
</section>
@endif

{{-- 4. Phản hồi & báo sự cố (không bao giờ công khai) --}}
<section class="rounded-md bg-white p-4 shadow-sm ring-1 ring-black/5">
    <h3 class="mb-1 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-green-700">
        <span class="h-4 w-1 rounded bg-green-600"></span> Phản hồi &amp; báo sự cố
    </h3>
    <p class="mb-3 text-xs text-gray-500">Gặp vấn đề với sản phẩm? Phản hồi được gắn với mã {{ strtoupper($trace->traceCode) }} để {{ $trace->brand }} truy lại đúng lô hàng.</p>
    <form data-trace-review-form action="{{ route('trace.reviews.store') }}" method="POST" novalidate class="space-y-3">
        <input type="hidden" name="trace_code" value="{{ $trace->traceCode }}">
        <input type="hidden" name="type" value="issue">
        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
        <label class="block text-sm">
            <span class="mb-1 block font-medium text-gray-700">Loại vấn đề <span class="text-red-500">*</span></span>
            <select name="issue_category" required class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm focus:border-green-600">
                <option value="">— Chọn loại vấn đề —</option>
                @foreach(\Modules\SalesOrder\Models\TraceReview::ISSUE_CATEGORIES as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
            </select>
        </label>
        <label class="block text-sm">
            <span class="mb-1 block font-medium text-gray-700">Mô tả vấn đề <span class="text-red-500">*</span></span>
            <textarea name="comment" rows="3" required minlength="10" maxlength="2000" placeholder="VD: rau bị dập, tem mờ không quét được, giao trễ…"
                      class="w-full rounded-lg border border-gray-300 px-2.5 py-2 text-sm focus:border-green-600"></textarea>
        </label>
        <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200">🔒 Không công khai thông tin cá nhân. Phản hồi chỉ dùng nội bộ để kiểm tra và xử lý lô hàng.</p>
        <button type="submit" class="w-full rounded-full border border-green-600 py-2.5 text-sm font-semibold text-green-700 transition hover:bg-green-50 disabled:opacity-70">Gửi phản hồi</button>
    </form>
</section>
