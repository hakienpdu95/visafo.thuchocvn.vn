<?php

namespace Modules\SalesOrder\Database\Seeders;

use App\Models\User;
use App\Services\Media\MediaUploadService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Models\InternalFacility;
use Modules\GoodsReceipt\Models\BatchQualityCheck;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\GoodsReceiptItem;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\LabelTemplate\Models\LabelTemplate;
use Modules\Product\Models\DocumentMasterType;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingLog;
use Modules\Product\Models\FarmingSource;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\Vendor\Models\Vendor;

/**
 * Dữ liệu giả lập cho trang truy xuất sâu (CHỈ dùng ở môi trường dev) — 3 kịch bản, mức độ đầy đủ khác nhau:
 *  - /trace/demotracea1  Rau từ nông hộ: đủ vùng trồng, nhật ký canh tác (có ảnh), QC 5 khâu, đã giao.
 *  - /trace/demotraceb1  Vùng trồng tự quản của VISAFO: thiếu QC cảm quan, ít nhật ký hơn, đã giao.
 *  - /trace/demotracec1  Hàng thương mại (NCC không ghi nhật ký canh tác): chưa xuất kho, chưa QC trước xuất.
 * Kèm 4 hồ sơ doanh nghiệp công khai (ảnh + PDF) cho tab Thương hiệu. Mọi bản ghi mang mã/ghi chú "DEMO".
 *
 *   php artisan db:seed --class="Modules\SalesOrder\Database\Seeders\TraceabilityDemoSeeder"
 */
class TraceabilityDemoSeeder extends Seeder
{
    private const FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Không chạy dữ liệu giả lập trên production.');

            return;
        }
        if (PrintLog::withTrashed()->where('trace_code', 'demotracea1')->exists()) {
            $this->command?->warn('Dữ liệu giả lập đã có (demotracea1) — bỏ qua.');

            return;
        }

        $admin = User::role('system_admin')->first();
        auth()->login($admin);

        DB::transaction(function () {
            $this->companyDocuments();

            $products = [
                'a' => Product::where('sku', '264MBV')->firstOrFail(), // Cải Ngọt
                'b' => Product::where('sku', '258MBV')->firstOrFail(), // Cà Chua
                'c' => Product::where('sku', '110MBV')->firstOrFail(), // Tỏi cộc
            ];

            $farmer = Vendor::create([
                'name' => 'HTX Rau an toàn Văn Đức (DEMO)', 'tax_code' => 'DEMO-0101000001',
                'address' => 'Thôn Trung Quan, xã Văn Đức, huyện Gia Lâm, Hà Nội', 'status' => 'active',
            ]);
            $this->sourceCertificate($farmer, 'VietGAP-TT-2026-0118');
            $own = Vendor::where('tax_code', $this->hqTaxCode())->first() ?? Vendor::create([
                'name' => 'VISAFO - Vùng trồng tự quản', 'tax_code' => $this->hqTaxCode(),
                'address' => 'Xã Phúc Thịnh, Hà Nội', 'status' => 'active',
            ]);
            $trader = Vendor::create([
                'name' => 'Công ty TNHH Nông sản Lý Sơn (DEMO)', 'tax_code' => 'DEMO-4300000002',
                'address' => 'Huyện Lý Sơn, Quảng Ngãi', 'status' => 'active',
            ]);

            // ── Kịch bản A: nông hộ, đủ dữ liệu ──
            $fbA = $this->farm($farmer, $products['a'], 'VD-DEMO-01', 'Ruộng rau số 3 — HTX Văn Đức', 'Cánh đồng Trung Quan, xã Văn Đức, Gia Lâm, Hà Nội', 2.4, 'Nước sông Hồng qua hệ thống lọc lắng', 'SP-DEMO-A01', sowDaysAgo: 40, harvestDaysAgo: 2, logs: [
                ['cultivation', 42, null, 'Làm đất, lên luống, bón vôi khử chua', 'demo-lam-dat.jpg'],
                ['fertilizer', 30, 25, 'Bón thúc lần 1', 'demo-bon-phan.jpg'],
                ['water', 20, null, 'Tưới nhỏ giọt buổi sáng', null],
                ['pesticide', 14, 0.5, 'Phòng trừ sâu tơ', 'demo-phun-thuoc.jpg'],
                ['harvest', 2, 850, 'Thu hoạch sáng sớm, sơ chế tại ruộng', 'demo-thu-hoach.jpg'],
            ]);
            // ── Kịch bản B: vùng trồng tự quản VISAFO, ít nhật ký ──
            $fbB = $this->farm($own, $products['b'], 'VSF-VT-DEMO-01', 'Nhà màng số 1 — VISAFO', 'Xã Phúc Thịnh, Hà Nội', 0.8, 'Giếng khoan, đạt QCVN 08', 'SP-DEMO-B01', sowDaysAgo: 75, harvestDaysAgo: 1, logs: [
                ['fertilizer', 50, 40, 'Bón lót phân hữu cơ vi sinh', null],
                ['harvest', 1, 320, 'Thu hái quả chín đỏ', 'demo-ca-chua.jpg'],
            ]);

            $order = SalesOrder::create([
                'misa_ref_id' => 'DEMO-XK-0001', 'customer_name' => 'Trường Mầm non Hoa Sen',
                'delivery_address' => 'Số 12 ngõ 5 Nguyễn Văn Cừ, Long Biên, Hà Nội', 'delivery_date' => today(),
                'source_file_name' => 'DEMO',
            ]);
            $order2 = SalesOrder::create([
                'misa_ref_id' => 'DEMO-XK-0002', 'customer_name' => 'Bếp ăn Công ty ABC',
                'delivery_address' => 'KCN Thăng Long, Đông Anh, Hà Nội', 'delivery_date' => today()->addDay(),
                'source_file_name' => 'DEMO',
            ]);

            $logA = $this->lot('a', $farmer, $products['a'], $fbA, $order, 1, 'NK-DEMO-0001', receiptDaysAgo: 1, weight: 0.5, qc: ['receiving' => 'pass', 'sensory' => 'pass']);
            $logB = $this->lot('b', $own, $products['b'], $fbB, $order, 2, 'NK-DEMO-0002', receiptDaysAgo: 1, weight: 1, qc: ['receiving' => 'pass']);
            $this->lot('c', $trader, $products['c'], null, $order2, 1, 'NK-DEMO-0003', receiptDaysAgo: 6, weight: 0.25, qc: ['receiving' => 'pass', 'sensory' => 'pass']);

            foreach ([$logA, $logB] as $log) {
                BatchQualityCheck::create(['sales_order_item_id' => $log->order_item_id, 'stage' => 'pre_dispatch', 'result' => 'pass',
                    'checked_at' => now()->subHours(4), 'checked_by' => auth()->id(), 'note' => 'DEMO']);
            }
            $order->update(['delivery_code' => 'GH-DEMO-0001', 'shipped_at' => now()->subHours(3), 'delivered_at' => now()->subMinutes(45), 'status' => SalesOrder::STATUS_DELIVERED]);
        });

        foreach (['a', 'b', 'c'] as $k) {
            $this->command?->info(route('trace.show', 'demotrace' . $k . '1'));
        }
    }

    /** @param array<int, array{0: string, 1: int, 2: ?float, 3: string, 4: ?string}> $logs [loại, số ngày trước, số lượng, ghi chú, ảnh] */
    private function farm(Vendor $vendor, Product $product, string $sourceCode, string $sourceName, string $address, float $area, string $water, string $batchCode, int $sowDaysAgo, int $harvestDaysAgo, array $logs): FarmingBatch
    {
        $partnerProduct = PartnerProduct::create(['vendor_id' => $vendor->id, 'product_id' => $product->id, 'name' => $product->name]);
        $source = FarmingSource::create([
            'vendor_id' => $vendor->id, 'source_code' => $sourceCode, 'name' => $sourceName, 'address' => $address,
            'area_hectare' => $area, 'water_source' => $water, 'status' => 'passed',
            'pre_season_checked_at' => now()->subDays($sowDaysAgo + 5)->setTime(9, 0), 'pre_season_checked_by' => auth()->id(), 'notes' => 'DEMO',
        ]);
        $batch = FarmingBatch::create([
            'farming_source_id' => $source->id, 'vendor_id' => $vendor->id, 'partner_product_id' => $partnerProduct->id,
            'agri_seed_id' => DB::table('agri_seeds')->value('id'), 'batch_code' => $batchCode,
            'sowing_date' => today()->subDays($sowDaysAgo), 'expected_harvest_date' => today()->subDays($harvestDaysAgo),
            'actual_harvest_date' => today()->subDays($harvestDaysAgo), 'status' => 'harvested',
            'pre_harvest_status' => 'passed', 'pre_harvest_checked_at' => now()->subDays($harvestDaysAgo + 1)->setTime(16, 30),
            'pre_harvest_checked_by' => auth()->id(), 'notes' => 'DEMO',
        ]);

        foreach ($logs as [$type, $daysAgo, $qty, $note, $image]) {
            FarmingLog::create([
                'farming_batch_id'   => $batch->id,
                'activity_type'      => $type,
                'activity_date'      => now()->subDays($daysAgo)->setTime(6, 30),
                'agri_fertilizer_id' => $type === 'fertilizer' ? DB::table('agri_fertilizers')->value('id') : null,
                'agri_pesticide_id'  => $type === 'pesticide' ? DB::table('agri_pesticides')->value('id') : null,
                'safe_harvest_date'  => $type === 'pesticide' ? today()->subDays($daysAgo - 7) : null,
                'quantity'           => $qty,
                'unit'               => match ($type) { 'pesticide' => 'lít', default => 'kg' },
                'method_or_target'   => $note,
                'image_path'         => $image ? $this->farmPhoto($image, $note) : null,
                'created_by'         => auth()->id(),
            ]);
        }

        return $batch;
    }

    /** Phiếu nhập + lô (gắn lô canh tác) + QC + dòng đơn bán + tem in. */
    private function lot(string $key, Vendor $vendor, Product $product, ?FarmingBatch $farmingBatch, SalesOrder $order, int $line, string $receiptCode, int $receiptDaysAgo, float $weight, array $qc): PrintLog
    {
        $receipt = GoodsReceipt::create(['misa_ref_id' => $receiptCode, 'vendor_id' => $vendor->id, 'supplier_name' => $vendor->name,
            'receipt_date' => today()->subDays($receiptDaysAgo), 'source_file_name' => 'DEMO']);
        GoodsReceiptItem::create(['goods_receipt_id' => $receipt->id, 'product_id' => $product->id, 'line_no' => 1,
            'product_name_raw' => $product->name, 'unit_raw' => 'kg', 'quantity' => 50]);
        $mfg = today()->subDays($receiptDaysAgo);
        $exp = $mfg->copy()->addDays($product->shelf_life_days ?: ($key === 'c' ? 90 : 5));
        $batch = ProductBatch::create(['batch_code' => $receiptCode . '-' . $product->sku, 'product_id' => $product->id, 'goods_receipt_id' => $receipt->id,
            'farming_batch_id' => $farmingBatch?->id, 'initial_qty' => 50, 'current_qty' => 40, 'mfg_date' => $mfg, 'exp_date' => $exp]);

        $offset = 0;
        foreach ($qc as $stage => $result) {
            BatchQualityCheck::create(['product_batch_id' => $batch->id, 'stage' => $stage, 'result' => $result,
                'checked_at' => now()->subDays($receiptDaysAgo)->setTime(7, 15 + 30 * $offset++), 'checked_by' => auth()->id(), 'note' => 'DEMO']);
        }

        $item = SalesOrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'line_no' => $line,
            'product_name_raw' => $product->name, 'unit_raw' => 'kg', 'requested_qty' => 10, 'actual_qty' => 10, 'printed_qty' => 10]);

        $log = PrintLog::create([
            'order_item_id' => $item->id, 'trace_code' => 'demotrace' . $key . '1', 'print_session_id' => Str::lower((string) Str::ulid()),
            'label_template_id' => LabelTemplate::where('view_path', 'labels.templates.visafo_80x60')->value('id'),
            'weight_per_label' => $weight, 'mfg_date' => $mfg, 'exp_date' => $exp, 'vendor_id' => $vendor->id, 'product_batch_id' => $batch->id,
            'batch_code' => 'LOT-' . $mfg->format('dmy') . '-' . $exp->format('dmy'), 'printed_by' => auth()->id(), 'status' => 'active',
        ]);
        // Đóng gói sau QC tiếp nhận vài giờ
        DB::table('print_logs')->where('id', $log->id)->update(['created_at' => now()->subDays($receiptDaysAgo)->setTime(10, 0)]);

        return $log->fresh();
    }

    /** Hồ sơ doanh nghiệp công khai của trụ sở chính (tab Thương hiệu): 2 ảnh + 2 PDF. */
    private function companyDocuments(): void
    {
        $hq = InternalFacility::where('type', 'headquarter')->firstOrFail();
        $uploader = app(MediaUploadService::class);

        foreach ([
            ['internal_business_registration', '0108150635', 'Sở KH&ĐT TP. Hà Nội', null, 'png'],
            ['facility_attp', '125/2025/NNPTNT-HN', 'Chi cục Chất lượng, Chế biến và Phát triển thị trường Hà Nội', 3, 'pdf'],
            ['internal_haccp', 'HACCP-VN-2025-0417', 'Tổ chức chứng nhận QUACERT', 2, 'png'],
            ['internal_capability_profile', null, null, null, 'pdf'],
        ] as [$code, $number, $issuedBy, $years, $ext]) {
            $type = DocumentMasterType::where('code', $code)->first();
            if ($type === null) {
                continue;
            }
            $doc = new ComplianceDocument([
                'document_master_type_id' => $type->id, 'document_number' => $number, 'issued_by' => $issuedBy,
                'issue_date' => today()->subMonths(8), 'expiration_date' => $years ? today()->addYears($years) : null,
                'status' => ComplianceDocumentStatus::Active, 'notes' => 'DEMO',
            ]);
            $doc->documentable()->associate($hq);
            $doc->save();

            $path = sys_get_temp_dir() . '/demo-' . $code . '.' . $ext;
            $ext === 'pdf' ? $this->writePdf($path, $type->name, $number) : $this->writeDocImage($path, $type->name, $number, $issuedBy);
            $uploader->upload(new UploadedFile($path, 'DEMO ' . $type->name . '.' . $ext, $ext === 'pdf' ? 'application/pdf' : 'image/png', null, true), $doc, 'attachments_private');
        }
    }

    /** Chứng nhận VietGAP của nông hộ (ảnh scan giả lập) — nút "Hồ sơ nguồn" trên trang truy xuất. */
    public function sourceCertificate(Vendor $vendor, string $number): void
    {
        $type = DocumentMasterType::where('code', 'supplier_vietgap')->first();
        if ($type === null) {
            return;
        }
        $doc = new ComplianceDocument([
            'document_master_type_id' => $type->id, 'document_number' => $number, 'issued_by' => 'Trung tâm Chứng nhận VietGAP (DEMO)',
            'issue_date' => today()->subMonths(5), 'expiration_date' => today()->addYears(2),
            'status' => ComplianceDocumentStatus::Active, 'notes' => 'DEMO',
        ]);
        $doc->documentable()->associate($vendor);
        $doc->save();

        $path = sys_get_temp_dir() . '/demo-vietgap-' . $vendor->id . '.png';
        $this->writeDocImage($path, 'Giấy chứng nhận VietGAP', $number, $vendor->name);
        app(MediaUploadService::class)->upload(new UploadedFile($path, 'DEMO VietGAP.png', 'image/png', null, true), $doc, 'attachments_private');
    }

    private function hqTaxCode(): string
    {
        return trim((string) InternalFacility::where('type', 'headquarter')->value('tax_code')) ?: '0108150635';
    }

    /** Ảnh minh chứng nhật ký canh tác giả lập (disk public, như ảnh nông hộ chụp). */
    private function farmPhoto(string $name, string $caption): string
    {
        $img = imagecreatetruecolor(640, 480);
        $top = imagecolorallocate($img, 120, 190, 110);
        $ground = imagecolorallocate($img, 120, 85, 50);
        imagefilledrectangle($img, 0, 0, 640, 300, $top);
        imagefilledrectangle($img, 0, 300, 640, 480, $ground);
        for ($x = 30; $x < 640; $x += 60) {
            imagefilledellipse($img, $x, 300, 50, 70, imagecolorallocate($img, 40, 130 + ($x % 60), 50));
        }
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 410, 640, 480, imagecolorallocatealpha($img, 0, 0, 0, 50));
        imagettftext($img, 18, 0, 20, 445, $white, self::FONT, $caption);
        imagettftext($img, 12, 0, 20, 468, $white, self::FONT, 'Ảnh minh chứng — DỮ LIỆU GIẢ LẬP');
        ob_start();
        imagejpeg($img, null, 82);
        $path = 'farming-logs/' . $name;
        Storage::disk('public')->put($path, ob_get_clean());

        return $path;
    }

    private function writeDocImage(string $path, string $title, ?string $number, ?string $issuedBy): void
    {
        $img = imagecreatetruecolor(900, 1200);
        imagefill($img, 0, 0, imagecolorallocate($img, 253, 250, 240));
        $ink = imagecolorallocate($img, 30, 30, 30);
        $red = imagecolorallocate($img, 190, 30, 30);
        imagerectangle($img, 30, 30, 870, 1170, $ink);
        imagettftext($img, 18, 0, 210, 110, $ink, self::FONT, 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM');
        imagettftext($img, 15, 0, 300, 145, $ink, self::FONT, 'Độc lập - Tự do - Hạnh phúc');
        imagettftext($img, 26, 0, 80, 300, $red, self::FONT, mb_strtoupper($title));
        imagettftext($img, 18, 0, 80, 400, $ink, self::FONT, 'Số: ' . ($number ?? '—'));
        imagettftext($img, 18, 0, 80, 450, $ink, self::FONT, 'Đơn vị: CÔNG TY CỔ PHẦN THỰC PHẨM VISAFO');
        imagettftext($img, 18, 0, 80, 500, $ink, self::FONT, 'Cơ quan cấp: ' . ($issuedBy ?? '—'));
        imagettftext($img, 40, 20, 220, 900, imagecolorallocatealpha($img, 200, 0, 0, 90), self::FONT, 'DỮ LIỆU GIẢ LẬP');
        imagepng($img, $path);
    }

    /** PDF 1 trang tối giản (chữ ASCII — font chuẩn PDF không có dấu tiếng Việt). */
    private function writePdf(string $path, string $title, ?string $number): void
    {
        $text = fn (string $s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], Str::ascii($s));
        $stream = "BT /F1 22 Tf 60 760 Td (" . $text(mb_strtoupper($title)) . ") Tj ET\n"
            . "BT /F1 14 Tf 60 720 Td (So: " . $text($number ?? '-') . ") Tj ET\n"
            . "BT /F1 14 Tf 60 696 Td (CONG TY CO PHAN THUC PHAM VISAFO) Tj ET\n"
            . "BT /F1 30 Tf 140 420 Td (DU LIEU GIA LAP - DEMO) Tj ET\n";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        file_put_contents($path, $pdf);
    }
}
