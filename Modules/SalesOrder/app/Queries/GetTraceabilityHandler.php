<?php

namespace Modules\SalesOrder\Queries;

use App\Models\Media;
use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Models\InternalFacility;
use Modules\GoodsReceipt\Enums\QualityCheckStage;
use Modules\GoodsReceipt\Models\BatchQualityCheck;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Enums\DocumentGroupType;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingLog;
use Modules\Product\Models\FarmingSource;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Support\TraceabilityData;
use Modules\Vendor\Models\Vendor;

class GetTraceabilityHandler implements QueryHandlerInterface
{
    /**
     * Loại hồ sơ được phép công khai ở mục "Tiêu chuẩn áp dụng" (theo document_master_types.code).
     * Whitelist thay vì lấy hết: CCCD, hợp đồng, hóa đơn, sổ nội bộ... tuyệt đối không công bố.
     */
    private const PUBLIC_STANDARD_CODES = [
        'product_declaration', // Tự công bố / Đăng ký bản công bố
        'supplier_vietgap',    // VietGAP / GlobalGAP
        'supplier_gmp',
        'supplier_ocop',
        'supplier_vet',        // Kiểm dịch thú y
        'supplier_attp',       // Cơ sở đủ điều kiện ATTP (NCC)
    ];

    /** Hồ sơ chung của chính doanh nghiệp (documentable_type = null) được công khai. */
    private const PUBLIC_COMPANY_STANDARD_CODES = ['facility_attp', 'internal_haccp'];

    /** Định dạng file hồ sơ doanh nghiệp được phát ra trang công khai (xem trực tiếp trên trình duyệt). */
    public const PUBLIC_DOCUMENT_MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    private const ACTIVITY_LABELS = [
        'cultivation' => 'Canh tác',
        'water'       => 'Tưới nước',
        'fertilizer'  => 'Bón phân',
        'pesticide'   => 'Phun thuốc BVTV',
        'harvest'     => 'Thu hoạch',
        'other'       => 'Chăm sóc',
    ];

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
                'productBatch.farmingBatch.farmingSource',
                'productBatch.farmingBatch.logs.vendorFarmingStep', 'productBatch.farmingBatch.logs.agriFertilizer', 'productBatch.farmingBatch.logs.agriPesticide',
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
            weight: str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) . ' kg',
            mfgDate: $log->mfg_date ?? $batch?->mfg_date,
            expDate: $log->exp_date ?? $batch?->exp_date,
            attributes: $log->attributes
                ->map(fn ($a) => ['key' => $a->attribute_key, 'value' => (string) $a->attribute_value])
                ->values()->all(),
            company: $company,
            producer: [
                // Không có NCC / vùng trồng tự quản → chính doanh nghiệp là đơn vị sản xuất / kinh doanh
                'name'      => $supplierText ?: $company['name'],
                'address'   => ($vendor && ! $isOwnFarm) ? $this->vendorAddress($vendor) : ($supplierText ? null : ($company['address'] ?: null)),
                'taxCode'   => $isOwnFarm ? ($company['taxCode'] ?: null) : ($vendor?->tax_code ?: null),
                'isVendor'  => (bool) $supplierText,
                'isOwnFarm' => $isOwnFarm,
            ],
            location: $source ? [
                'code'        => $source->source_code,
                'name'        => $source->name,
                'address'     => $source->address,
                'area'        => $source->area_hectare !== null ? (float) $source->area_hectare : null,
                'waterSource' => $source->water_source ?: null,
                'harvestedAt' => $farmingBatch->actual_harvest_date,
            ] : null,
            batchCode: $batchCode,
            timeline: $this->timeline($log, $farmingBatch, $receipt?->receipt_date ?? $batch?->created_at, $supplierText, $batchCode, $order, $qualityChecks, (string) config('trace.company_name', 'VISAFO')),
            standards: $this->standards($product, $vendor),
            status: $log->status,
            statusReason: $log->status_reason,
            supplier: $supplierText ? [
                'name'    => $supplierText,
                'address' => $vendor ? $this->vendorAddress($vendor) : null,
            ] : null,
            relatedProducts: $this->relatedProducts($item, $vendor, $supplierText, $product),
            brandStory: trim((string) config('trace.brand_story')),
            documentGroups: $this->documentGroups($log->trace_code),
            qualityChecks: $this->qualityCheckRows($farmingBatch, $qualityChecks),
            delivery: $this->delivery($order),
        );
    }

    /**
     * Sản phẩm khác trong CÙNG đơn bán hàng có tem in từ CÙNG nguồn cung (NCC chọn lúc in, NCC của lô nhập,
     * hoặc tên nguồn cung nhập tay); chỉ SP đang kinh doanh, SP đang xem đứng đầu.
     * Chỉ có đúng SP đang xem → trả rỗng (view ẩn danh sách).
     *
     * @return array<int, array{name: string, image: ?string, isCurrent: bool}>
     */
    private function relatedProducts(SalesOrderItem $item, ?Vendor $vendor, ?string $supplierText, ?Product $current): array
    {
        if ($vendor === null && ! $supplierText) {
            return [];
        }

        $sameSupplier = fn (Builder $q) => $q
            ->where('status', PrintLogStatus::Active->value)
            ->where(fn (Builder $w) => $vendor
                ? $w->where('vendor_id', $vendor->id)
                    ->orWhere(fn (Builder $b) => $b->whereNull('vendor_id')
                        ->whereHas('productBatch.goodsReceipt', fn (Builder $r) => $r->where('vendor_id', $vendor->id)))
                : $w->whereNull('vendor_id')->where('supplier_name', $supplierText));

        $products = SalesOrderItem::query()
            ->where('order_id', $item->order_id)
            ->whereHas('printLogs', $sameSupplier)
            ->with('product.media')
            ->orderBy('line_no')
            ->get()
            ->pluck('product')
            ->filter(fn (?Product $p) => $p?->status === ProductStatus::Active)
            ->unique('id')
            ->sortByDesc(fn (Product $p) => $p->id === $current?->id)
            ->values();

        if ($products->count() < 2) {
            return [];
        }

        return $products->map(fn (Product $p) => [
            'name'      => $p->name,
            'image'     => $p->galleryImages()->first()['thumb_url'] ?? ($p->image_url ?: null),
            'isCurrent' => $p->id === $current?->id,
        ])->all();
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
     * Khối "Kiểm soát chất lượng": QC phía nông hộ (nếu có lô canh tác) + 3 khâu của VISAFO (chưa ghi → result null).
     * Chỉ công khai khâu, kết quả, thời điểm — không có ghi chú/người kiểm.
     *
     * @return array<int, array{label: string, owner: string, result: ?string, at: mixed}>
     */
    private function qualityCheckRows(?FarmingBatch $farmingBatch, Collection $checks): array
    {
        $rows = [];
        $source = $farmingBatch?->farmingSource;

        if ($source?->pre_season_checked_at && in_array($source->status, ['passed', 'failed'], true)) {
            $rows[] = ['label' => 'Kiểm tra vùng trồng trước vụ', 'owner' => 'Vùng trồng', 'result' => $source->status === 'passed' ? 'pass' : 'fail', 'at' => $source->pre_season_checked_at];
        }
        if ($farmingBatch?->pre_harvest_checked_at && $farmingBatch->pre_harvest_status === 'passed') {
            $rows[] = ['label' => 'Phê duyệt thu hoạch (hết cách ly BVTV)', 'owner' => 'Vùng trồng', 'result' => 'pass', 'at' => $farmingBatch->pre_harvest_checked_at];
        }

        foreach (QualityCheckStage::cases() as $stage) {
            $check = $checks->get($stage->value);
            $rows[] = ['label' => $stage->label(), 'owner' => 'VISAFO', 'result' => $check?->result->value, 'at' => $check?->checked_at];
        }

        return $rows;
    }

    /** @return array{code: string, shippedAt: mixed, deliveredAt: mixed, recipient: ?string}|null */
    private function delivery(?SalesOrder $order): ?array
    {
        if ($order?->shipped_at === null) {
            return null;
        }

        return [
            'code'        => (string) $order->delivery_code,
            'shippedAt'   => $order->shipped_at,
            'deliveredAt' => $order->delivered_at,
            'recipient'   => $this->maskName($order->customer_name),
        ];
    }

    /** Tiền tố loại đơn vị giữ nguyên khi che tên điểm nhận (so khớp không phân biệt hoa thường, dài nhất trước). */
    private const RECIPIENT_TYPE_PREFIXES = [
        'Trường mầm non', 'Trường tiểu học', 'Trường THCS', 'Trường THPT', 'Trường MN', 'Trường TH',
        'Bếp ăn', 'Công ty TNHH', 'Công ty cổ phần', 'Công ty CP', 'Công ty', 'Nhà hàng', 'Khách sạn',
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

    /** @return array<int, array{icon: string, title: string, description: string, at: mixed, done: bool, image?: ?string}> */
    private function timeline(PrintLog $log, ?FarmingBatch $farmingBatch, $receivedAt, ?string $supplier, ?string $batchCode, ?SalesOrder $order, Collection $checks, string $brand): array
    {
        $farmSteps = [];

        if ($farmingBatch !== null) {
            $source = $farmingBatch->farmingSource;

            if ($source?->pre_season_checked_at && in_array($source->status, ['passed', 'failed'], true)) {
                $farmSteps[] = [
                    'icon'        => 'shield',
                    'title'       => 'Kiểm tra vùng trồng trước vụ',
                    'description' => ($source->status === 'passed' ? 'Đạt' : 'Không đạt') . ' — vùng trồng ' . $source->name . '.',
                    'at'          => $source->pre_season_checked_at,
                    'done'        => $source->status === 'passed',
                ];
            }

            $farmSteps[] = [
                'icon'        => 'seed',
                'title'       => 'Gieo trồng',
                'description' => 'Lô canh tác ' . $farmingBatch->batch_code . ($source ? ' tại vùng trồng ' . $source->name : '') . '.',
                'at'          => $farmingBatch->sowing_date,
                'done'        => $farmingBatch->sowing_date !== null,
            ];

            $hasHarvestLog = false;
            foreach ($farmingBatch->logs as $farmingLog) {
                $hasHarvestLog = $hasHarvestLog || $farmingLog->activity_type === 'harvest';
                $farmSteps[] = [
                    'icon'        => $farmingLog->activity_type === 'harvest' ? 'harvest' : 'farm',
                    'title'       => $farmingLog->vendorFarmingStep?->step_name
                        ?? self::ACTIVITY_LABELS[$farmingLog->activity_type] ?? 'Chăm sóc',
                    'description' => $this->farmingLogDetail($farmingLog),
                    'at'          => $farmingLog->activity_date,
                    'done'        => true,
                    'image'       => $farmingLog->image_path ? Storage::disk('public')->url($farmingLog->image_path) : null,
                ];
            }

            if ($farmingBatch->pre_harvest_checked_at && $farmingBatch->pre_harvest_status === 'passed') {
                $farmSteps[] = [
                    'icon'        => 'shield',
                    'title'       => 'Phê duyệt thu hoạch',
                    'description' => 'QC xác nhận đã hết thời gian cách ly thuốc BVTV, đủ điều kiện thu hoạch.',
                    'at'          => $farmingBatch->pre_harvest_checked_at,
                    'done'        => true,
                ];
            }

            if (! $hasHarvestLog && $farmingBatch->actual_harvest_date) {
                $farmSteps[] = [
                    'icon'        => 'harvest',
                    'title'       => 'Thu hoạch',
                    'description' => 'Thu hoạch lô ' . $farmingBatch->batch_code . '.',
                    'at'          => $farmingBatch->actual_harvest_date,
                    'done'        => true,
                ];
            }

            // Xếp theo thời gian (bước chưa có ngày đứng đầu, giữ thứ tự khai báo khi trùng)
            $farmSteps = collect($farmSteps)->sortBy(fn ($st) => $st['at']?->getTimestamp() ?? PHP_INT_MIN)->values()->all();
        }

        $qcStep = fn (QualityCheckStage $stage, string $icon) => ($check = $checks->get($stage->value)) ? [[
            'icon'        => $icon,
            'title'       => $stage->label(),
            'description' => $check->result->label() . ' — ' . $brand . ' kiểm soát.',
            'at'          => $check->checked_at,
            'done'        => true,
        ]] : [];

        $steps = [
            ...$farmSteps,
            [
                'icon'        => 'warehouse',
                'title'       => 'Tiếp nhận nguyên liệu / Nhập kho',
                'description' => $batchCode
                    ? 'Lô ' . $batchCode . ' nhập kho' . ($supplier ? ' từ ' . $supplier : '') . '.'
                    : ($supplier ? 'Tiếp nhận hàng từ ' . $supplier . '.' : 'Đang cập nhật thông tin lô hàng.'),
                'at'          => $receivedAt,
                'done'        => $receivedAt !== null || $batchCode !== null,
            ],
            ...$qcStep(QualityCheckStage::Receiving, 'shield'),
            ...$qcStep(QualityCheckStage::Sensory, 'shield'),
            [
                'icon'        => 'package',
                'title'       => 'Sơ chế / Đóng gói',
                'description' => 'Đóng gói và gắn mã truy xuất ' . strtoupper($log->trace_code) . '.',
                'at'          => $log->created_at,
                'done'        => true,
            ],
            ...$qcStep(QualityCheckStage::PreDispatch, 'shield'),
            [
                'icon'        => 'truck',
                'title'       => 'Xuất kho / Vận chuyển',
                'description' => $order?->shipped_at
                    ? 'Đã xuất kho' . ($order->delivery_code ? ', vận đơn ' . $order->delivery_code : '') . '.'
                    : 'Đã lập lệnh xuất kho, chờ giao hàng.',
                'at'          => $order?->shipped_at,
                'done'        => $order?->shipped_at !== null,
            ],
        ];

        if ($order?->shipped_at !== null) {
            $recipient = $this->maskName($order->customer_name);
            $steps[] = [
                'icon'        => 'pin',
                'title'       => 'Giao hàng thành công',
                'description' => $order->delivered_at ? 'Đã giao đến ' . ($recipient ?? 'khách hàng') . '.' : 'Đang giao hàng.',
                'at'          => $order->delivered_at,
                'done'        => $order->delivered_at !== null,
            ];
        }

        return $steps;
    }

    private function farmingLogDetail(FarmingLog $log): string
    {
        $qty = $log->quantity ? ' — ' . rtrim(rtrim((string) $log->quantity, '0'), '.') . ' ' . $log->unit : '';

        $detail = match ($log->activity_type) {
            'fertilizer' => ($log->agriFertilizer?->name ?? 'Phân bón') . $qty,
            'pesticide'  => ($log->agriPesticide?->trade_name ?? 'Thuốc BVTV') . $qty
                . ($log->safe_harvest_date ? ' · Cách ly đến ' . $log->safe_harvest_date->format('d/m/Y') : ''),
            'harvest'    => $log->quantity ? 'Sản lượng' . $qty : '',
            default      => '',
        };

        return trim(implode(' · ', array_filter([$detail, $log->method_or_target, $log->notes])));
    }

    private function vendorAddress(Vendor $vendor): ?string
    {
        $parts = array_filter([$vendor->address, $vendor->ward?->name, $vendor->province?->name]);

        return $parts ? implode(', ', array_unique($parts)) : null;
    }

    /**
     * Hồ sơ tiêu chuẩn đang hiệu lực của sản phẩm, NCC, sản phẩm-của-NCC và hồ sơ chung của doanh nghiệp.
     *
     * @return array<int, array{name: string, number: ?string, issuedBy: ?string, expiresAt: mixed, owner: string}>
     */
    private function standards(?Product $product, ?Vendor $vendor): array
    {
        $partnerProduct = ($product && $vendor)
            ? PartnerProduct::query()->where('vendor_id', $vendor->id)->where('product_id', $product->id)->first()
            : null;

        $hqId = InternalFacility::query()->where('type', 'headquarter')->value('id');

        $owners = array_filter([
            'Sản phẩm'          => $product,
            'Nhà cung cấp'      => $vendor,
            'Sản phẩm của NCC'  => $partnerProduct,
        ]);

        return ComplianceDocument::query()
            ->with('documentType')
            ->where('status', ComplianceDocumentStatus::Active->value)
            ->where(fn (Builder $q) => $q->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()))
            ->where(function (Builder $q) use ($owners, $hqId) {
                foreach ($owners as $model) {
                    $q->orWhere(fn (Builder $o) => $o
                        ->where('documentable_type', $model->getMorphClass())
                        ->where('documentable_id', $model->getKey())
                        ->whereHas('documentType', fn (Builder $t) => $t->whereIn('code', self::PUBLIC_STANDARD_CODES)));
                }
                // Chứng nhận của chính doanh nghiệp (HACCP, ATTP): hồ sơ chung hoặc hồ sơ gắn trụ sở chính —
                // mọi lô hàng đều qua kiểm soát chất lượng của doanh nghiệp nên kế thừa chứng nhận này.
                $q->orWhere(fn (Builder $o) => $o
                    ->where(fn (Builder $w) => $w->whereNull('documentable_type')
                        ->when($hqId, fn (Builder $h) => $h->orWhere(fn (Builder $f) => $f
                            ->where('documentable_type', (new InternalFacility())->getMorphClass())
                            ->where('documentable_id', $hqId))))
                    ->whereHas('documentType', fn (Builder $t) => $t->whereIn('code', self::PUBLIC_COMPANY_STANDARD_CODES)));
            })
            ->orderBy('issue_date')
            ->get()
            ->map(fn (ComplianceDocument $doc) => [
                'name'      => $doc->custom_name ?: ($doc->documentType?->name ?? 'Hồ sơ tiêu chuẩn'),
                'number'    => $doc->document_number ?: null,
                'issuedBy'  => $doc->issued_by ?: null,
                'expiresAt' => $doc->expiration_date,
                'owner'     => in_array($doc->documentable_type, [null, (new InternalFacility())->getMorphClass()], true)
                    ? 'Đơn vị phân phối'
                    : (array_search($doc->documentable_type, array_map(fn ($m) => $m->getMorphClass(), $owners), true) ?: 'Sản phẩm'),
            ])
            ->unique(fn ($d) => $d['name'] . '|' . $d['number'])
            ->values()
            ->all();
    }

    /** @return array{name: string, address: string, hotline: string, taxCode: string} */
    private function company(): array
    {
        $hq = InternalFacility::query()->where('type', 'headquarter')->with(['province', 'ward'])->first();

        // Tên pháp nhân lấy từ hồ sơ doanh nghiệp (company_name), không dùng tên cơ sở — đó là nhãn nội bộ, VD "Trụ sở chính (Công ty)"
        return [
            'name'    => $hq?->company_name ?: ((string) config('trace.company_legal_name') ?: (string) config('trace.company_name', 'VISAFO')),
            'address' => $hq?->fullAddress() ?: config('trace.company_address', ''),
            'hotline' => (string) config('trace.company_hotline', ''),
            'taxCode' => trim((string) $hq?->tax_code),
        ];
    }
}
