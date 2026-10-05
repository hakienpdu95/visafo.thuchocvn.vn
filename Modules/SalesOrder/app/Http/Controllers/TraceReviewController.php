<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\SalesOrder\Actions\StoreTraceReviewAction;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Enums\TraceReviewType;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\TraceReview;

/** Tab "Đánh giá" trên trang truy xuất CÔNG KHAI (không đăng nhập) — nhận đánh giá & báo sự cố qua Ajax. */
class TraceReviewController extends Controller
{
    public function store(Request $request, StoreTraceReviewAction $action): JsonResponse
    {
        // Honeypot: ô "website" ẩn với người, bot tự điền → giả vờ thành công, không lưu
        if (filled($request->input('website'))) {
            return response()->json(['message' => 'Cảm ơn bạn đã gửi phản hồi.']);
        }

        $isRating = $request->input('type') === TraceReviewType::Rating->value;
        $score = [$isRating ? 'required' : 'nullable', 'integer', 'between:1,5'];

        $data = $request->validate([
            'trace_code'          => ['required', 'string', 'max:32'],
            'type'                => ['required', Rule::enum(TraceReviewType::class)],
            'quality_score'       => $score,
            'delivery_score'      => $score,
            'packaging_score'     => $score,
            'traceability_score'  => $score,
            'issue_category'      => [$isRating ? 'nullable' : 'required', Rule::in(array_keys(TraceReview::ISSUE_CATEGORIES))],
            'comment'             => [$isRating ? 'nullable' : 'required', 'string', $isRating ? 'min:0' : 'min:10', 'max:2000'],
            'is_public_requested' => ['nullable', 'boolean'],
        ], [
            '*.required'          => 'Vui lòng chọn điểm cho tất cả tiêu chí.',
            'issue_category.required' => 'Vui lòng chọn loại vấn đề.',
            'comment.required'    => 'Vui lòng mô tả vấn đề bạn gặp.',
            'comment.min'         => 'Mô tả vấn đề cần ít nhất 10 ký tự.',
            'comment.max'         => 'Nội dung tối đa 2000 ký tự.',
            '*.between'           => 'Điểm phải từ 1 đến 5.',
        ]);

        $log = PrintLog::query()->where('trace_code', strtolower($data['trace_code']))->with('orderItem')->first();
        abort_if($log === null || $log->status !== PrintLogStatus::Active, 404, 'Mã truy xuất không hợp lệ.');

        $review = $action->handle($log, $data);

        return response()->json([
            'message' => $review->type === TraceReviewType::Rating
                ? 'Cảm ơn bạn đã đánh giá! Đánh giá sẽ được hiển thị sau khi VISAFO xác minh.'
                : 'Đã ghi nhận phản hồi. VISAFO sẽ kiểm tra lô hàng và xử lý sớm nhất.',
        ], 201);
    }
}
