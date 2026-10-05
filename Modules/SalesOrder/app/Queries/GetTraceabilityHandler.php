<?php

namespace Modules\SalesOrder\Queries;

use App\Models\Media;
use App\Shared\Contracts\QueryHandlerInterface;
use App\Support\Html\RichHtmlSanitizer;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Models\InternalFacility;
use Modules\GoodsReceipt\Enums\QualityCheckStage;
use Modules\GoodsReceipt\Models\BatchQualityCheck;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Enums\DocumentGroupType;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingSource;
use Modules\Product\Models\Product;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Models\TraceReview;
use Modules\SalesOrder\Support\TraceabilityData;
use Modules\Vendor\Models\Vendor;

class GetTraceabilityHandler implements QueryHandlerInterface
{
    /** Chứng nhận vùng trồng / cơ sở của nguồn cung (gắn NCC hoặc mặt hàng của NCC) — nút "Hồ sơ nguồn". */
    private const SOURCE_DOCUMENT_CODES = ['supplier_vietgap', 'supplier_gmp', 'supplier_ocop', 'supplier_vet', 'supplier_attp'];

    /** Định dạng file hồ sơ doanh nghiệp được phát ra trang công khai (xem trực tiếp trên trình duyệt). */
    public const PUBLIC_DOCUMENT_MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** @return TraceabilityData|null null khi mã truy xuất không tồn tại */
    public function handle(QueryInterface $query): ?TraceabilityData
    {
        /** @var GetTraceabilityQuery $query */
        $log = PrintLog::query()
            ->where('trace_code', $query->traceCode)
            ->with([
                'orderItem.product.category', 'orderItem.product.media', 'orderItem.salesOrder', 'attributes',
                'vendor.province', 'vendor.ward', 'productBatch.goodsReceipt.vendor.province', 'productBatch.goodsReceipt.vendor.ward',
                'productBatch.qualityChecks', 'orderItem.qualityChecks',
                'productBatch.farmingBatch.farmingSource', 'productBatch.farmingBatch.vendor', 'productBatch.farmingBatch.partnerProduct',
                'productBatch.farmingBatch.logs',
            ])
            ->first();

        if ($log === null || $log->orderItem === null) {
            return null;
        }

        $item = $log->orderItem;
        $order = $item->salesOrder;
        $product = $item->product;

        // Lô nhập: ưu tiên lô đã chọn khi in tem; tem cũ chưa lưu lô thì lấy lô nhập gần nhất trước lúc in.
        $batch = $log->productBatch ?? $this->guessBatch($log, $item->product_id);
        $receipt = $batch?->goodsReceipt;

        $vendor = $log->vendor ?? $receipt?->vendor;
        if ($vendor !== null && ! $vendor->relationLoaded('province')) {
            $vendor->load(['province', 'ward']);
        }

        $company = $this->company();
        // Lô canh tác chỉ lấy theo khóa product_batches.farming_batch_id của lô đã chọn khi in — không đoán theo mã lô / ngày
        // (đoán sai khi một NCC có nhiều vụ chồng nhau), cũng không đi qua lô nhập đoán ở guessBatch().
        $farmingBatch = $log->productBatch?->farmingBatch;
        $source = $farmingBatch?->farmingSource;
        $isOwnFarm = $vendor !== null && $this->isOwnVendor($vendor, $company);
        $qualityChecks = $this->qualityChecks($log->productBatch?->qualityChecks ?? collect(), $item->qualityChecks);

        // Ảnh chính đã ở đầu nhờ order_column; chưa upload ảnh thì dùng image_url (Sapo)
        $productImages = $product?->galleryImages()->pluck('url')->all() ?: array_filter([$product?->image_url]);

        // Vendor đại diện vùng trồng tự quản của VISAFO → không coi là nguồn cung bên ngoài
        $supplierText = $isOwnFarm ? null : ($vendor?->name ?: ($receipt?->supplier_name ?: $log->supplier_name));
        $batchCode = $log->batch_code ?: ($batch?->batch_code ?: $farmingBatch?->batch_code);

        $productName = $product?->name ?? $item->product_name_raw ?? '—';

        return new TraceabilityData(
            traceCode: $log->trace_code,
            productName: $productName,
            productDescription: trim((string) $product?->description) ?: $this->autoDescription($productName, $supplierText, $source, $company['name']),
            productImages: array_values($productImages),
            categoryName: $product?->category?->name,
            productSku: $product?->sku,
            brand: (string) config('trace.company_name', 'VISAFO'),
            weight: $this->weightText($log),
            mfgDate: $log->mfg_date ?? $batch?->mfg_date,
            expDate: $log->exp_date ?? $batch?->exp_date,
            packedAt: $log->created_at,
            batchQuantity: $this->batchQuantity($log->productBatch, $item),
            attributes: $log->attributes
                ->map(fn ($a) => ['key' => $a->attribute_key, 'value' => (string) $a->attribute_value])
                ->values()->all(),
            company: $company,
            location: $source ? [
                'code'        => $source->source_code,
                'name'        => $source->name,
                'address'     => $source->address,
                'area'        => $source->area_hectare !== null ? (float) $source->area_hectare : null,
                'waterSource' => $source->water_source ?: null,
                'harvestedAt' => $farmingBatch->actual_harvest_date,
                'batchCode'   => $farmingBatch->batch_code,
                'vendorName'  => $farmingBatch->vendor?->name,
                'isOwn'       => $farmingBatch->vendor !== null && $this->isOwnVendor($farmingBatch->vendor, $company),
                // QC phía vùng trồng (hiện trong khối Nguồn gốc): kiểm tra trước vụ, phê duyệt thu hoạch
                'preSeason'   => $source->pre_season_checked_at && in_array($source->status, ['passed', 'failed'], true) ? $source->status === 'passed' : null,
                'harvestApprovedAt' => $farmingBatch->pre_harvest_status === 'passed' ? $farmingBatch->pre_harvest_checked_at : null,
            ] : null,
            batchCode: $batchCode,
            status: $log->status,
            statusReason: $log->status_reason,
            brandStory: trim((string) config('trace.brand_story')),
            documentGroups: $this->documentGroups($log->trace_code),
            sourceDocuments: $this->sourceDocuments($farmingBatch, $log->trace_code),
            hasBatch: $log->productBatch !== null,
            qualityChecks: $this->qualityCheckRows($qualityChecks),
            qcConclusion: $this->qcConclusion($qualityChecks),
            // Tiếp nhận = ngày nhập trên phiếu (nghiệp vụ); lô tạo cùng ngày thì lấy luôn giờ tạo lô. created_at của lô là lúc
            // import file phiếu nhập — có thể muộn hơn tiếp nhận thực tế (và sau cả QC tiếp nhận).
            reviewSummary: $this->reviewSummary($product?->id),
            reviews: $this->publicReviews($product?->id),
            executedSteps: $this->executedSteps($log, $receipt?->receipt_date && $batch?->created_at?->isSameDay($receipt->receipt_date)
                ? $batch->created_at : ($receipt?->receipt_date ?? $batch?->created_at), $order, $qualityChecks),
            journey: $this->journey($log, $farmingBatch, $receipt?->receipt_date ?? $batch?->created_at, $order, $qualityChecks, $item),
            delivery: $this->delivery($order, $company),
        );
    }

    /**
     * Hồ sơ doanh nghiệp công khai cho tab "Thương hiệu", gom theo nhóm hồ sơ (thứ tự như các tab ở internal-compliance);
     * nhóm không có hồ sơ công khai nào bị bỏ. Chỉ phát tệp ảnh/PDF; file nằm ở disk private nên phát qua route
     * trace.document (kiểm tra lại whitelist mỗi lần tải), không lộ URL lưu trữ.
     *
     * @return array<int, array{label: string, documents: array<int, array{name: string, number: ?string, issuedBy: ?string, issuedAt: mixed, expiresAt: mixed, files: array<int, array{url: string, thumb: string, preview: string, isPdf: bool}>}>}>
     */
    private function documentGroups(string $traceCode): array
    {
        $byGroup = ComplianceDocument::query()
            ->publicCompanyProfile()
            ->with(['documentType', 'media'])
            ->orderBy('issue_date')
            ->get()
            ->groupBy(fn (ComplianceDocument $doc) => $doc->documentType?->document_group?->value);

        return collect(DocumentGroupType::cases())
            ->filter(fn (DocumentGroupType $group) => $byGroup->has($group->value))
            ->map(fn (DocumentGroupType $group) => [
                'label'     => $group->label(),
                'documents' => $byGroup[$group->value]->map(fn (ComplianceDocument $doc) => [
                    'name'      => $doc->custom_name ?: ($doc->documentType?->name ?? 'Hồ sơ doanh nghiệp'),
                    'number'    => $doc->document_number ?: null,
                    'issuedBy'  => $doc->issued_by ?: null,
                    'issuedAt'  => $doc->issue_date,
                    'expiresAt' => $doc->expiration_date,
                    'files'     => $doc->getMedia('attachments_private')
                        ->filter(fn (Media $m) => in_array($m->mime_type, self::PUBLIC_DOCUMENT_MIMES, true))
                        ->map(fn (Media $m) => [
                            'url'     => route('trace.document', ['trace_code' => $traceCode, 'media' => $m->id]),
                            'thumb'   => route('trace.document', ['trace_code' => $traceCode, 'media' => $m->id, 'variant' => 'thumb']),
                            'preview' => route('trace.document', ['trace_code' => $traceCode, 'media' => $m->id, 'variant' => 'preview']),
                            'isPdf'   => $m->mime_type === 'application/pdf',
                        ])
                        ->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Hồ sơ đang hiệu lực của nguồn (NCC của lô canh tác + mặt hàng NCC tương ứng) thuộc SOURCE_DOCUMENT_CODES.
     * Chỉ khi lô nhập đã liên kết lô canh tác. Dùng chung cho trang (nút "Hồ sơ nguồn") và route trace.document (whitelist).
     *
     * @return Builder<ComplianceDocument>|null
     */
    public static function sourceDocumentQuery(?FarmingBatch $farmingBatch): ?Builder
    {
        if ($farmingBatch === null) {
            return null;
        }

        $owners = array_filter([$farmingBatch->vendor, $farmingBatch->partnerProduct]);

        return ComplianceDocument::query()
            ->where('status', ComplianceDocumentStatus::Active->value)
            ->where(fn (Builder $q) => $q->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()))
            ->whereHas('documentType', fn (Builder $t) => $t->whereIn('code', self::SOURCE_DOCUMENT_CODES))
            ->where(function (Builder $q) use ($owners) {
                foreach ($owners as $model) {
                    $q->orWhere(fn (Builder $o) => $o->where('documentable_type', $model->getMorphClass())->where('documentable_id', $model->getKey()));
                }
            });
    }

    /** @return array<int, array{name: string, caption: string, files: array<int, array{url: string, preview: string, isPdf: bool}>}> chỉ hồ sơ có tệp xem được */
    private function sourceDocuments(?FarmingBatch $farmingBatch, string $traceCode): array
    {
        return (self::sourceDocumentQuery($farmingBatch)?->with(['documentType', 'media'])->orderBy('issue_date')->get() ?? collect())
            ->map(fn (ComplianceDocument $doc) => [
                'name'    => $doc->custom_name ?: ($doc->documentType?->name ?? 'Hồ sơ nguồn'),
                'caption' => implode(' · ', array_filter([
                    $doc->custom_name ?: $doc->documentType?->name,
                    $doc->document_number ? 'Số ' . $doc->document_number : null,
                    $doc->issued_by ? 'Cấp bởi ' . $doc->issued_by : null,
                    $doc->expiration_date ? 'Hiệu lực đến ' . $doc->expiration_date->format('d/m/Y') : null,
                ])),
                'files'   => $doc->getMedia('attachments_private')
                    ->filter(fn (Media $m) => in_array($m->mime_type, self::PUBLIC_DOCUMENT_MIMES, true))
                    ->map(fn (Media $m) => [
                        'url'     => route('trace.document', ['trace_code' => $traceCode, 'media' => $m->id]),
                        'preview' => route('trace.document', ['trace_code' => $traceCode, 'media' => $m->id, 'variant' => 'preview']),
                        'isPdf'   => $m->mime_type === 'application/pdf',
                    ])->values()->all(),
            ])
            ->filter(fn ($d) => $d['files'] !== [])
            ->values()->all();
    }

    /** Mô tả dự phòng khi sản phẩm chưa nhập mô tả: ghép từ dữ liệu thật (nguồn cung, vùng trồng), không bịa thông tin. */
    private function autoDescription(string $productName, ?string $supplier, ?FarmingSource $source, string $brand): string
    {
        $origin = match (true) {
            $source !== null  => ' được trồng tại ' . $source->name . ($source->address ? ' (' . $source->address . ')' : '')
                . ($supplier ? ', cung cấp bởi ' . $supplier : ''),
            (bool) $supplier  => ' được cung cấp bởi ' . $supplier,
            default           => '',
        };

        return $productName . $origin . ', sơ chế, đóng gói và kiểm soát chất lượng bởi ' . $brand
            . '. Mỗi sản phẩm mang một mã truy xuất riêng để bạn kiểm tra nguồn gốc, hạn sử dụng và các công đoạn sản xuất.';
    }

    /**
     * Khối "Hành trình hàng hóa": 5 mốc tóm tắt theo vòng đời lô (thu hoạch → tiếp nhận → QC → đóng gói → giao).
     * Chỉ các mốc đã có dữ liệu (chưa xuất kho thì không có mốc giao hàng).
     *
     * @return array<int, array{title: string, time: ?string, meta: ?string, done: bool, ok: ?bool}>
     */
    /**
     * Điểm chất lượng tổng của SẢN PHẨM (mọi lô): trung bình từng tiêu chí trên các đánh giá đã duyệt.
     *
     * @return array{average: ?float, count: int, criteria: array<string, ?float>}
     */
    private function reviewSummary(?string $productId): array
    {
        $cols = array_keys(TraceReview::SCORES);
        $row = $productId
            ? TraceReview::query()->approvedRatings()->where('product_id', $productId)
                ->selectRaw('COUNT(*) as total, ' . implode(', ', array_map(fn ($c) => "AVG($c) as $c", $cols)))
                ->toBase()->first()
            : null;
        $count = (int) ($row->total ?? 0);
        $criteria = [];
        foreach (TraceReview::SCORES as $col => $label) {
            $criteria[$label] = $count ? round((float) $row->{$col}, 1) : null;
        }
        $values = array_filter($criteria, fn ($v) => $v !== null);

        return ['average' => $values ? round(array_sum($values) / count($values), 1) : null, 'count' => $count, 'criteria' => $criteria];
    }

    /**
     * Nhận xét công khai: đánh giá đã duyệt VÀ khách đồng ý công khai, mới nhất trước (tối đa 10).
     * Tên điểm nhận đã che; có đơn bán liên kết → "Đã xác minh giao dịch".
     *
     * @return array<int, array{name: ?string, verified: bool, score: ?float, comment: ?string, at: mixed, lot: ?string}>
     */
    private function publicReviews(?string $productId): array
    {
        if ($productId === null) {
            return [];
        }

        return TraceReview::query()->approvedRatings()
            ->where('product_id', $productId)
            ->where('is_public_requested', true)
            ->with(['salesOrder:id,customer_name', 'printLog:id,batch_code,created_at'])
            ->latest()->limit(10)->get()
            ->map(fn (TraceReview $r) => [
                'name'     => $this->maskName($r->salesOrder?->customer_name),
                'verified' => $r->sales_order_id !== null,
                'score'    => $r->averageScore(),
                'comment'  => $r->comment,
                'at'       => $r->created_at,
                'lot'      => implode(' • ', array_filter([
                    $r->printLog?->batch_code ? 'Lô ' . $r->printLog->batch_code : null,
                    $r->printLog?->created_at ? 'đóng gói ' . $r->printLog->created_at->format('d/m/Y') : null,
                ])) ?: null,
            ])->all();
    }

    /**
     * Khối "VISAFO đã thực hiện với lô này" (tab VISAFO): tổng hợp tự động từ phiếu nhập, QC, tem in và đơn bán.
     * state: done (✓ + result xanh) | fail (✕ đỏ). Bước chưa có dữ liệu bị bỏ (không hiện "Chờ…").
     *
     * @return array<int, array{label: string, time: ?string, state: string, result: string}>
     */
    private function executedSteps(PrintLog $log, $receivedAt, ?SalesOrder $order, Collection $checks): array
    {
        $at = fn ($t) => $t ? $t->format($t->format('H:i') === '00:00' ? 'd/m/Y' : 'd/m/Y H:i') : null;
        $qc = function (Collection $group, string $waiting) use ($at): array {
            if ($group->isEmpty()) {
                return ['time' => null, 'state' => 'pending', 'result' => $waiting];
            }
            $failed = $group->contains(fn (BatchQualityCheck $c) => $c->result->value === 'fail');

            return ['time' => $at($group->max('checked_at')), 'state' => $failed ? 'fail' : 'done', 'result' => $failed ? 'Không đạt' : 'Đạt'];
        };

        $steps = [
            ['label' => 'Tiếp nhận', ...($receivedAt
                ? ['time' => $at($receivedAt), 'state' => 'done', 'result' => 'Hoàn thành']
                : ['time' => null, 'state' => 'pending', 'result' => 'Đang cập nhật'])],
            ['label' => 'Kiểm tra đầu vào', ...$qc($checks->filter(fn (BatchQualityCheck $c) => $c->stage->isBatchStage()), 'Đang cập nhật')],
            ['label' => 'Sơ chế / đóng gói', 'time' => $at($log->created_at), 'state' => 'done', 'result' => 'Hoàn thành'],
            ['label' => 'Kiểm tra trước xuất', ...$qc($checks->filter(fn (BatchQualityCheck $c) => $c->stage === QualityCheckStage::PreDispatch), 'Chờ kiểm tra')],
            ['label' => 'Xuất kho', ...($order?->shipped_at
                ? ['time' => $at($order->shipped_at), 'state' => 'done', 'result' => 'Hoàn thành']
                : ['time' => null, 'state' => 'pending', 'result' => 'Chờ xuất kho'])],
            ['label' => 'Giao khách hàng', ...match (true) {
                $order?->delivered_at !== null => ['time' => $at($order->delivered_at), 'state' => 'done', 'result' => 'Hoàn thành'],
                $order?->shipped_at !== null   => ['time' => null, 'state' => 'pending', 'result' => 'Đang giao'],
                default                        => ['time' => null, 'state' => 'pending', 'result' => 'Chờ giao hàng'],
            }],
        ];

        // Chỉ hiện bước đã thực hiện — lô đi tới đâu, danh sách dài tới đó
        return array_values(array_filter($steps, fn (array $st) => $st['state'] !== 'pending'));
    }

    private function journey(PrintLog $log, ?FarmingBatch $farmingBatch, $receivedAt, ?SalesOrder $order, Collection $checks, SalesOrderItem $item): array
    {
        $at = fn ($t) => $t ? $t->format($t->format('H:i') === '00:00' ? 'd/m/Y' : 'H:i • d/m/Y') : null;
        $brand = (string) config('trace.company_name', 'VISAFO');
        $steps = [];

        if ($farmingBatch !== null) {
            $source = $farmingBatch->farmingSource;
            $harvestedAt = $farmingBatch->logs->where('activity_type', 'harvest')->max('activity_date') ?? $farmingBatch->actual_harvest_date;
            if ($harvestedAt) {
                $steps[] = ['title' => 'Thu hoạch tại nguồn', 'time' => $at($harvestedAt),
                    'meta' => implode(' • ', array_filter([$source?->address ?: $source?->name, $source?->source_code])) ?: null, 'done' => true, 'ok' => null];
            }
        }

        if ($receivedAt) {
            $steps[] = ['title' => $brand . ' tiếp nhận', 'time' => $at($receivedAt),
                'meta' => implode(' • ', array_filter([$log->productBatch ? $this->batchQuantity($log->productBatch, $item) : null, 'Kho ' . $brand])), 'done' => true, 'ok' => null];
        }

        // QC gom nhóm: thời điểm = khâu kiểm tại kho mới nhất (tiếp nhận/cảm quan), chưa có thì lấy khâu trước xuất
        if ($checks->isNotEmpty()) {
            $batchChecks = $checks->filter(fn (BatchQualityCheck $c) => $c->stage->isBatchStage());
            $passed = $checks->every(fn (BatchQualityCheck $c) => $c->result->value === 'pass');
            $steps[] = ['title' => 'Kiểm tra chất lượng', 'time' => $at(($batchChecks->isNotEmpty() ? $batchChecks : $checks)->max('checked_at')),
                'meta' => 'Kết quả: ' . ($passed ? 'Đạt' : 'Không đạt') . ' (' . $checks->map(fn (BatchQualityCheck $c) => mb_strtolower(str_replace('Kiểm tra ', '', $c->stage->label())))->implode(', ') . ')',
                'done' => true, 'ok' => $passed];
        }

        $steps[] = ['title' => 'Sơ chế & đóng gói', 'time' => $at($log->created_at),
            'meta' => 'Quy cách ' . $this->weightText($log) . ' • Gắn mã ' . strtoupper($log->trace_code), 'done' => true, 'ok' => null];

        $shipped = $order?->shipped_at;
        $delivered = $order?->delivered_at;
        $steps[] = match (true) {
            $delivered !== null => ['title' => 'Xuất kho & giao hàng',
                'time' => $shipped->isSameDay($delivered)
                    ? $shipped->format('H:i') . '–' . $delivered->format('H:i') . ' • ' . $delivered->format('d/m/Y')
                    : $shipped->format('H:i d/m') . ' – ' . $delivered->format('H:i d/m/Y'),
                'meta' => 'Đã giao đến điểm nhận', 'done' => true, 'ok' => true],
            $shipped !== null => ['title' => 'Xuất kho & giao hàng', 'time' => $at($shipped), 'meta' => 'Đã xuất kho, đang giao hàng', 'done' => true, 'ok' => null],
            default => null,
        };

        return array_values(array_filter($steps));
    }

    /** "0,5 kg" — khối lượng mỗi tem. */
    private function weightText(PrintLog $log): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) . ' kg';
    }

    /**
     * Khối lượng lô: số lượng nhập ban đầu của lô đã chọn khi in (đơn vị theo dòng phiếu nhập); tem không gắn lô
     * thì lấy số lượng thực xuất (hoặc yêu cầu) của dòng đơn bán. Không dùng lô đoán ở guessBatch().
     */
    private function batchQuantity(?ProductBatch $batch, SalesOrderItem $item): ?string
    {
        $fmt = fn ($qty) => rtrim(rtrim(number_format((float) $qty, 3, ',', '.'), '0'), ',');

        if ($batch !== null) {
            $unit = $batch->goodsReceipt?->items()->where('product_id', $batch->product_id)->value('unit_raw')
                ?: ($item->product?->unit ?: 'kg');

            return $fmt($batch->initial_qty) . ' ' . $unit;
        }

        $qty = $item->actual_qty ?? $item->requested_qty;

        return $qty !== null ? $fmt($qty) . ' ' . ($item->unit_raw ?: 'kg') : null;
    }

    private function guessBatch(PrintLog $log, ?string $productId): ?ProductBatch
    {
        if ($productId === null) {
            return null;
        }

        $batchQuery = ProductBatch::query()->where('product_id', $productId)
            ->with(['goodsReceipt.vendor.province', 'goodsReceipt.vendor.ward']);

        return (clone $batchQuery)->where('created_at', '<=', $log->created_at)->latest('created_at')->first()
            ?? (clone $batchQuery)->latest('created_at')->first();
    }

    /**
     * QC nội bộ của VISAFO: kết quả mới nhất của mỗi khâu (tiếp nhận/cảm quan theo lô nhập, trước xuất theo dòng đơn).
     *
     * @return Collection<string, BatchQualityCheck> key = stage
     */
    private function qualityChecks(Collection $batchChecks, Collection $itemChecks): Collection
    {
        return $batchChecks->filter(fn (BatchQualityCheck $c) => $c->stage->isBatchStage())
            ->merge($itemChecks->filter(fn (BatchQualityCheck $c) => $c->stage === QualityCheckStage::PreDispatch))
            ->sortBy('checked_at')
            ->keyBy(fn (BatchQualityCheck $c) => $c->stage->value);
    }

    /**
     * Khối "Kiểm soát chất lượng": chỉ các khâu QC đã có kết quả (rỗng → view ẩn cả khối).
     * Chỉ công khai khâu, kết quả, thời điểm — không có ghi chú/người kiểm.
     *
     * @return array<int, array{label: string, result: ?string, at: mixed}>
     */
    private function qualityCheckRows(Collection $checks): array
    {
        return array_map(fn (QualityCheckStage $stage) => [
            'label'  => match ($stage) {
                QualityCheckStage::Receiving   => 'Kiểm tra khi tiếp nhận',
                QualityCheckStage::Sensory     => 'Kiểm tra cảm quan',
                QualityCheckStage::PreDispatch => 'Kiểm tra trước xuất',
            },
            'result' => $checks->get($stage->value)?->result->value,
            'at'     => $checks->get($stage->value)?->checked_at,
        ], array_values(array_filter(QualityCheckStage::cases(), fn (QualityCheckStage $stage) => $checks->has($stage->value))));
    }

    /** Kết luận lô (null = chưa đủ khâu QC, không hiển thị). */
    private function qcConclusion(Collection $checks): ?string
    {
        // Chỉ kết luận khi đủ mọi khâu QC bắt buộc (tiếp nhận, cảm quan, trước xuất) đã có kết quả
        if (count(array_filter(QualityCheckStage::cases(), fn (QualityCheckStage $stage) => ! $checks->has($stage->value))) > 0) {
            return null;
        }

        return $checks->contains(fn (BatchQualityCheck $c) => $c->result->value === 'fail') ? 'fail' : 'pass';
    }

    /**
     * Giao vận & điểm nhận (null = chưa xuất kho). Tên điểm nhận đã che; địa chỉ chỉ giữ cấp phường/quận + tỉnh.
     *
     * @return array{code: string, status: string, shippedAt: mixed, deliveredAt: mixed, recipient: ?string, area: ?string, warehouse: ?string}|null
     */
    private function delivery(?SalesOrder $order, array $company): ?array
    {
        if ($order?->shipped_at === null) {
            return null;
        }

        return [
            'code'        => (string) $order->delivery_code,
            'status'      => $order->delivered_at ? 'Đã hoàn thành' : 'Đang giao',
            'shippedAt'   => $order->shipped_at,
            'deliveredAt' => $order->delivered_at,
            'recipient'   => $this->maskName($order->customer_name),
            'area'        => $this->publicArea($order->delivery_address),
            'warehouse'   => $company['area'] ?: null,
        ];
    }

    /**
     * Địa chỉ công khai: chỉ 2 cấp hành chính cuối (VD "Số 12 ngõ 5 Nguyễn Văn Cừ, Long Biên, Hà Nội" → "Long Biên, Hà Nội"),
     * bỏ tiền tố Xã/Phường/Quận/Huyện/Thành phố/Tỉnh và "Việt Nam"; phần có chữ số (số nhà) không bao giờ công khai.
     */
    private function publicArea(?string $address): ?string
    {
        $parts = array_values(array_filter(array_map(
            fn (string $p) => trim((string) preg_replace('/^(xã|phường|thị trấn|quận|huyện|thị xã|thành phố|tp\.?|tỉnh)\s+/iu', '', trim($p))),
            explode(',', (string) $address),
        ), fn (string $p) => $p !== '' && mb_strtolower($p) !== 'việt nam' && ! preg_match('/\p{N}/u', $p)));

        return count($parts) >= 2 ? implode(', ', array_slice($parts, -2)) : null;
    }

    /** Tiền tố loại đơn vị giữ nguyên khi che tên điểm nhận (so khớp không phân biệt hoa thường, dài nhất trước). */
    private const RECIPIENT_TYPE_PREFIXES = [
        'Trường mầm non', 'Trường tiểu học', 'Trường THCS', 'Trường THPT', 'Trường MN', 'Trường TH',
        'Mầm non', 'Tiểu học', 'Nhà trẻ', 'Bệnh viện', 'Bếp ăn', 'Công ty TNHH', 'Công ty cổ phần', 'Công ty CP', 'Công ty', 'Nhà hàng', 'Khách sạn',
    ];

    /**
     * Che tên điểm nhận trên trang công khai: giữ tiền tố loại đơn vị (nếu có, giữ đúng cách viết gốc), phần tên riêng
     * chỉ giữ chữ cái đầu. Không có tiền tố → giữ từ đầu tiên. Từ có chữ số (số nhà, SĐT) che toàn bộ.
     * VD "Trường Mầm non Hoa Sen" → "Trường Mầm non H*** S***".
     */
    private function maskName(?string $name): ?string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) $name));
        if ($name === '') {
            return null;
        }

        $prefixes = self::RECIPIENT_TYPE_PREFIXES;
        usort($prefixes, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $kept = null;
        foreach ($prefixes as $prefix) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(?=\s|$)/iu', $name, $m)) {
                $kept = $m[0];
                break;
            }
        }

        // Bỏ dấu câu rời ("-", ",")
        $words = array_values(array_filter(
            preg_split('/\s+/u', $kept !== null ? mb_substr($name, mb_strlen($kept)) : $name, -1, PREG_SPLIT_NO_EMPTY),
            fn (string $w) => preg_match('/[\p{L}\p{N}]/u', $w),
        ));

        $masked = array_map(
            fn (string $w, int $i) => match (true) {
                (bool) preg_match('/\p{N}/u', $w) => '***',
                $kept === null && $i === 0       => $w,
                default                          => mb_substr($w, 0, 1) . '***',
            },
            $words, array_keys($words),
        );

        return implode(' ', array_filter([$kept, ...$masked])) ?: null;
    }

    /** Vendor đại diện vùng trồng tự quản: khai báo ID ở TRACE_OWN_VENDOR_IDS, hoặc trùng MST với hồ sơ trụ sở chính. */
    private function isOwnVendor(Vendor $vendor, array $company): bool
    {
        return in_array($vendor->id, (array) config('trace.own_vendor_ids', []), true)
            || ($company['taxCode'] !== '' && $vendor->tax_code !== null && trim($vendor->tax_code) === $company['taxCode']);
    }

    /** @return array{name: string, address: string, hotline: string, taxCode: string, area: string, supplyChainRole: ?string} */
    private function company(): array
    {
        $hq = InternalFacility::query()->where('type', 'headquarter')->with(['province', 'ward'])->first();

        // Tên pháp nhân lấy từ hồ sơ doanh nghiệp (company_name), không dùng tên cơ sở — đó là nhãn nội bộ, VD "Trụ sở chính (Công ty)"
        return [
            'name'    => $hq?->company_name ?: ((string) config('trace.company_legal_name') ?: (string) config('trace.company_name', 'VISAFO')),
            'address' => $hq?->fullAddress() ?: config('trace.company_address', ''),
            'hotline' => (string) config('trace.company_hotline', ''),
            'taxCode' => trim((string) $hq?->tax_code),
            'area'    => $this->publicArea($hq?->fullAddress() ?: (string) config('trace.company_address', '')) ?? '',
            // "Vai trò trong chuỗi cung ứng" (HTML Jodit) — làm sạch lại khi xuất ra trang công khai
            'supplyChainRole' => RichHtmlSanitizer::clean($hq?->supply_chain_role),
        ];
    }
}
