<?php

namespace Modules\SalesOrder\Queries;

use App\Models\Media;
use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Builder;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Models\InternalFacility;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingLog;
use Modules\Product\Models\FarmingSource;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
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
        $farmingBatch = ($vendor && $product) ? $this->farmingBatch($log, $vendor, $product) : null;
        $source = $farmingBatch?->farmingSource;

        // Ảnh chính đã ở đầu nhờ order_column; chưa upload ảnh thì dùng image_url (Sapo)
        $productImages = $product?->galleryImages()->pluck('url')->all() ?: array_filter([$product?->image_url]);

        $supplierText = $vendor?->name ?: ($receipt?->supplier_name ?: $log->supplier_name);
        $batchCode = $log->batch_code ?: ($batch?->batch_code ?: $farmingBatch?->batch_code);

        $productName = $product?->name ?? $item->product_name_raw ?? '—';

        return new TraceabilityData(
            traceCode: $log->trace_code,
            productName: $productName,
            productSubtitle: $product?->category?->name ?: (string) config('trace.company_slogan'),
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
                // Không có NCC → chính doanh nghiệp là đơn vị sản xuất / kinh doanh
                'name'     => $supplierText ?: $company['name'],
                'address'  => $vendor ? $this->vendorAddress($vendor) : ($supplierText ? null : ($company['address'] ?: null)),
                'taxCode'  => $vendor?->tax_code ?: null,
                'isVendor' => (bool) $supplierText,
            ],
            location: $source ? [
                'code'    => $source->source_code,
                'name'    => $source->name,
                'address' => $source->address,
            ] : null,
            batchCode: $batchCode,
            timeline: $this->timeline($log, $farmingBatch, $receipt?->receipt_date ?? $batch?->created_at, $supplierText, $batchCode, $order),
            standards: $this->standards($product, $vendor),
            status: $log->status,
            statusReason: $log->status_reason,
            supplier: $supplierText ? [
                'name'    => $supplierText,
                'address' => $vendor ? $this->vendorAddress($vendor) : null,
            ] : null,
            relatedProducts: $this->relatedProducts($item, $vendor, $supplierText, $product),
            brandStory: trim((string) config('trace.brand_story')),
            companyDocuments: $this->companyDocuments($log->trace_code),
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
     * Hồ sơ doanh nghiệp công khai cho tab "Thương hiệu". Chỉ phát tệp ảnh/PDF; file nằm ở disk private nên
     * phát qua route trace.document (kiểm tra lại whitelist mỗi lần tải), không lộ URL lưu trữ.
     *
     * @return array<int, array{name: string, number: ?string, issuedBy: ?string, issuedAt: mixed, expiresAt: mixed, files: array<int, array{url: string, thumb: string, preview: string, isPdf: bool}>}>
     */
    private function companyDocuments(string $traceCode): array
    {
        return ComplianceDocument::query()
            ->publicCompanyProfile()
            ->with(['documentType', 'media'])
            ->orderBy('issue_date')
            ->get()
            ->map(fn (ComplianceDocument $doc) => [
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
            ])
            ->values()  // hồ sơ chưa có bản scan (ảnh/PDF) vẫn hiện tên, số hiệu, hiệu lực
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
     * Lô canh tác (nhật ký đồng ruộng) của NCC cho sản phẩm này: khớp đúng mã lô nếu có,
     * không thì lấy lô đã thu hoạch gần nhất trước thời điểm in tem.
     */
    private function farmingBatch(PrintLog $log, Vendor $vendor, Product $product): ?FarmingBatch
    {
        $base = FarmingBatch::query()
            ->where('vendor_id', $vendor->id)
            ->whereHas('partnerProduct', fn (Builder $q) => $q->where('product_id', $product->id))
            ->with(['farmingSource', 'logs.vendorFarmingStep', 'logs.agriFertilizer', 'logs.agriPesticide']);

        if ($log->batch_code && ($exact = (clone $base)->where('batch_code', $log->batch_code)->first())) {
            return $exact;
        }

        return (clone $base)
            ->whereNotNull('actual_harvest_date')
            ->whereDate('actual_harvest_date', '<=', $log->created_at)
            ->latest('actual_harvest_date')
            ->first();
    }

    /** @return array<int, array{icon: string, title: string, description: string, at: mixed, done: bool}> */
    private function timeline(PrintLog $log, ?FarmingBatch $farmingBatch, $receivedAt, ?string $supplier, ?string $batchCode, $order): array
    {
        $steps = [];

        if ($farmingBatch !== null) {
            $source = $farmingBatch->farmingSource;
            $steps[] = [
                'icon'        => 'seed',
                'title'       => 'Gieo trồng',
                'description' => 'Lô canh tác ' . $farmingBatch->batch_code . ($source ? ' tại vùng trồng ' . $source->name : '') . '.',
                'at'          => $farmingBatch->sowing_date,
                'done'        => $farmingBatch->sowing_date !== null,
            ];

            $hasHarvestLog = false;
            foreach ($farmingBatch->logs->sortBy('activity_date') as $farmingLog) {
                $hasHarvestLog = $hasHarvestLog || $farmingLog->activity_type === 'harvest';
                $steps[] = [
                    'icon'        => $farmingLog->activity_type === 'harvest' ? 'harvest' : 'farm',
                    'title'       => $farmingLog->vendorFarmingStep?->step_name
                        ?? self::ACTIVITY_LABELS[$farmingLog->activity_type] ?? 'Chăm sóc',
                    'description' => $this->farmingLogDetail($farmingLog),
                    'at'          => $farmingLog->activity_date,
                    'done'        => true,
                ];
            }

            if (! $hasHarvestLog && $farmingBatch->actual_harvest_date) {
                $steps[] = [
                    'icon'        => 'harvest',
                    'title'       => 'Thu hoạch',
                    'description' => 'Thu hoạch lô ' . $farmingBatch->batch_code . '.',
                    'at'          => $farmingBatch->actual_harvest_date,
                    'done'        => true,
                ];
            }
        }

        $orderShipped = $order !== null && $order->status !== 'pending';

        $steps[] = [
            'icon'        => 'warehouse',
            'title'       => 'Tiếp nhận nguyên liệu / Nhập kho',
            'description' => $batchCode
                ? 'Lô ' . $batchCode . ' nhập kho' . ($supplier ? ' từ ' . $supplier : '') . '.'
                : ($supplier ? 'Tiếp nhận hàng từ ' . $supplier . '.' : 'Đang cập nhật thông tin lô hàng.'),
            'at'          => $receivedAt,
            'done'        => $receivedAt !== null || $batchCode !== null,
        ];
        $steps[] = [
            'icon'        => 'package',
            'title'       => 'Sơ chế / Đóng gói / Kiểm định chất lượng',
            'description' => 'Đóng gói và gắn mã truy xuất ' . strtoupper($log->trace_code) . '.',
            'at'          => $log->created_at,
            'done'        => true,
        ];
        $steps[] = [
            'icon'        => 'truck',
            'title'       => 'Vận chuyển / Giao hàng',
            'description' => $orderShipped ? 'Đã xuất kho giao đến khách hàng.' : 'Đã lập lệnh xuất kho, chờ giao hàng.',
            'at'          => $orderShipped ? $order->updated_at : null,
            'done'        => $orderShipped,
        ];

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

        $owners = array_filter([
            'Sản phẩm'          => $product,
            'Nhà cung cấp'      => $vendor,
            'Sản phẩm của NCC'  => $partnerProduct,
        ]);

        return ComplianceDocument::query()
            ->with('documentType')
            ->where('status', ComplianceDocumentStatus::Active->value)
            ->where(fn (Builder $q) => $q->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()))
            ->where(function (Builder $q) use ($owners) {
                foreach ($owners as $model) {
                    $q->orWhere(fn (Builder $o) => $o
                        ->where('documentable_type', $model->getMorphClass())
                        ->where('documentable_id', $model->getKey())
                        ->whereHas('documentType', fn (Builder $t) => $t->whereIn('code', self::PUBLIC_STANDARD_CODES)));
                }
                $q->orWhere(fn (Builder $o) => $o
                    ->whereNull('documentable_type')
                    ->whereHas('documentType', fn (Builder $t) => $t->whereIn('code', self::PUBLIC_COMPANY_STANDARD_CODES)));
            })
            ->orderBy('issue_date')
            ->get()
            ->map(fn (ComplianceDocument $doc) => [
                'name'      => $doc->custom_name ?: ($doc->documentType?->name ?? 'Hồ sơ tiêu chuẩn'),
                'number'    => $doc->document_number ?: null,
                'issuedBy'  => $doc->issued_by ?: null,
                'expiresAt' => $doc->expiration_date,
                'owner'     => $doc->documentable_type === null
                    ? 'Đơn vị phân phối'
                    : (array_search($doc->documentable_type, array_map(fn ($m) => $m->getMorphClass(), $owners), true) ?: 'Sản phẩm'),
            ])
            ->unique(fn ($d) => $d['name'] . '|' . $d['number'])
            ->values()
            ->all();
    }

    /** @return array{name: string, address: string, hotline: string} */
    private function company(): array
    {
        $hq = InternalFacility::query()->where('type', 'headquarter')->with(['province', 'ward'])->first();

        // Tên pháp nhân lấy từ hồ sơ doanh nghiệp (company_name), không dùng tên cơ sở — đó là nhãn nội bộ, VD "Trụ sở chính (Công ty)"
        return [
            'name'    => $hq?->company_name ?: ((string) config('trace.company_legal_name') ?: (string) config('trace.company_name', 'VISAFO')),
            'address' => $hq?->fullAddress() ?: config('trace.company_address', ''),
            'hotline' => (string) config('trace.company_hotline', ''),
        ];
    }
}
