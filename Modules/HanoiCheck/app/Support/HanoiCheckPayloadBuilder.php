<?php

namespace Modules\HanoiCheck\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Compliance\Models\InternalFacility;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\Product\Models\Product;
use Modules\Vendor\Models\Vendor;

class HanoiCheckPayloadBuilder
{
    public const CERTIFICATE_TYPES = [
        'supplier_attp', 'supplier_vietgap', 'supplier_gmp', 'haccp_ncc', 'chung_nhan_iso_ncc', 'supplier_ocop', 'supplier_vet',
    ];

    private const IMAGE_PATTERN = '/\.(jpe?g|png|gif|webp|pdf)(\?.*)?$/i';

    public function build(SyncEntity $entity, ?Model $model): array
    {
        return match ($entity) {
            SyncEntity::Master       => $this->master(),
            SyncEntity::Vendor       => $this->vendor($model),
            SyncEntity::Product      => $this->product($model),
            SyncEntity::ProductBatch => $this->batch($model),
            default                  => ['requests' => [], 'errors' => []],
        };
    }

    private function master(): array
    {
        $d = config('services.hanoicheck.defaults');
        $facility = InternalFacility::query()->where('type', 'headquarter')->first();
        $name = $facility?->company_name ?: config('company.locked.company_name');
        $address = Str::limit((string) ($facility?->address ?: config('company.locked.address')), 252);

        $warehouse = array_filter([
            'ma_kho'    => $d['ma_kho'],
            'ten_kho'   => 'Kho ' . $name,
            'dia_chi'   => $address,
            'dien_tich' => filled($d['warehouse_area']) ? (float) $d['warehouse_area'] : null,
        ], fn ($v) => $v !== null);

        $errors = [];
        foreach (['ma_co_so', 'ma_kho', 'ma_khau', 'ma_quy_trinh'] as $key) {
            if (! HanoiCheckCodes::valid($d[$key])) {
                $errors[] = "{$key} \"{$d[$key]}\" chỉ được chứa chữ cái, số, dấu _ và -.";
            }
        }

        return [
            'requests' => [
                ['path' => '/api/supplier/facilities/merge', 'records' => [['ma_co_so' => $d['ma_co_so'], 'ten_co_so' => Str::limit($name, 252), 'dia_chi' => $address]]],
                ['path' => '/api/supplier/warehouses/merge', 'records' => [$warehouse]],
                ['path' => '/api/supplier/steps/merge', 'records' => [['ma_khau' => $d['ma_khau'], 'ten_khau' => $d['ten_khau'], 'ghi_chu' => 'Tiếp nhận, kiểm tra và nhập kho lô hàng từ nhà cung ứng đầu vào']]],
                ['path' => '/api/supplier/processes/merge', 'records' => [[
                    'ma_quy_trinh'   => $d['ma_quy_trinh'],
                    'ten_quy_trinh'  => $d['ten_quy_trinh'],
                    'loai_san_pham'  => 'food',
                    'danh_sach_khau' => [['ma_khau' => $d['ma_khau'], 'thu_tu' => 1]],
                ]]],
            ],
            'errors' => $errors,
        ];
    }

    private function vendor(Vendor $vendor): array
    {
        $groups = $vendor->partnerProducts()->with('product.category:id,code')->get()
            ->map(fn ($pp) => self::foodCategoryId($pp->product))
            ->filter()->map(fn ($id) => (int) $id)->unique()->values()->all()
            ?: config('services.hanoicheck.defaults.nhom_thuc_pham');
        $groups = array_values(array_filter(array_map('intval', (array) $groups)));

        $certificates = $vendor->activeDocuments()
            ->with('documentType:id,code,name')
            ->get()
            ->filter(fn ($doc) => in_array($doc->documentType?->code, self::CERTIFICATE_TYPES, true))
            ->sortByDesc(fn ($doc) => $doc->issue_date?->timestamp ?? 0)
            ->unique(fn ($doc) => $doc->documentType->name)
            ->map(fn ($doc) => $this->withValidRange([
                'ten_giay_chung_nhan' => Str::limit($doc->documentType->name, 252),
                'so_giay'             => $doc->document_number ? Str::limit($doc->document_number, 252) : null,
                'ngay_cap'            => $doc->issue_date?->toDateString(),
                'ngay_het_han'        => $doc->expiration_date?->toDateString(),
            ], 'ngay_cap'))
            ->values()->all();

        $contracts = $vendor->contracts()
            ->whereNotNull('contract_number')
            ->get()
            ->unique('contract_number')
            ->map(fn ($c) => $this->withValidRange([
                'so_hop_dong'  => Str::limit($c->contract_number, 252),
                'ngay_ky'      => $c->start_date?->toDateString(),
                'ngay_het_han' => $c->end_date?->toDateString(),
            ], 'ngay_ky'))
            ->values()->all();

        $record = $this->clean([
            'ma_ncc_dau_vao'       => HanoiCheckCodes::for(SyncEntity::Vendor, $vendor),
            'ten'                  => Str::limit($vendor->name, 252),
            'nhom_thuc_pham'       => $groups,
            'ma_so_thue'           => $this->limit($vendor->tax_code, 20),
            'dia_chi'              => $this->limit($vendor->address, 255),
            'dien_thoai'           => $this->limit($vendor->phone_number, 20),
            'giay_chung_nhan_attp' => $certificates ?: null,
            'hop_dong'             => $contracts ?: null,
        ]);

        $errors = $groups === [] ? ['nhom_thuc_pham: chưa gán nhóm thực phẩm chuẩn cho NCC (HANOICHECK_CATEGORY_MAP theo danh mục sản phẩm hoặc HANOICHECK_DEFAULT_FOOD_GROUP_IDS).'] : [];

        return ['requests' => [['path' => '/api/supplier/sub-suppliers/merge', 'records' => [$record]]], 'errors' => $errors];
    }

    private function product(Product $product): array
    {
        $category = self::foodCategoryId($product);

        $record = $this->clean([
            'ma_san_pham'  => HanoiCheckCodes::for(SyncEntity::Product, $product),
            'ten_san_pham' => Str::limit($product->name, 252),
            'ma_loai_sp'   => filled($category) ? (string) $category : null,
            'quoc_gia'     => 'Việt Nam',
            'mo_ta'        => $this->limit(strip_tags((string) ($product->description ?: $product->product_info)), 5000),
            'ma_quy_trinh' => config('services.hanoicheck.defaults.ma_quy_trinh'),
        ]);

        $errors = blank($category) ? ['ma_loai_sp: chưa gán id Danh mục thực phẩm chuẩn (HANOICHECK_CATEGORY_MAP theo danh mục sản phẩm hoặc HANOICHECK_DEFAULT_FOOD_CATEGORY_ID).'] : [];

        return ['requests' => [['path' => '/api/supplier/foods/merge', 'records' => [$record]]], 'errors' => $errors];
    }

    private function batch(ProductBatch $batch): array
    {
        $d = config('services.hanoicheck.defaults');
        $batch->loadMissing(['product', 'goodsReceipt.vendor']);
        $receipt = $batch->goodsReceipt;
        $vendor = $receipt?->vendor;
        $errors = [];

        if ($batch->product === null) {
            return ['requests' => [], 'errors' => ['ma_san_pham: lô không gắn sản phẩm.']];
        }

        $code = HanoiCheckCodes::for(SyncEntity::ProductBatch, $batch);
        $vendorCode = $vendor ? HanoiCheckCodes::for(SyncEntity::Vendor, $vendor) : null;
        $importedAt = $receipt?->receipt_date ?? $batch->created_at;
        $mfg = $batch->mfg_date && $batch->mfg_date->lte(today()) ? $batch->mfg_date->toDateString() : null;

        $images = collect([$batch->product->mainImageUrl()])
            ->filter(fn ($url) => self::publicFileUrl($url) && ! str_contains($url, 'drive.google.com'))
            ->take(3)
            ->map(fn ($url) => ['ten_anh' => Str::limit($batch->product->name, 252), 'duong_dan' => $url, 'loai' => 'image'])
            ->values()->all();
        if ($images === []) {
            $errors[] = 'danh_sach_anh: cần 1–3 ảnh có đường dẫn công khai (https, tên miền thật, đuôi .jpg/.png/.webp/.pdf) của lô hoặc sản phẩm.';
        }

        $record = $this->clean([
            'ma_san_pham'     => HanoiCheckCodes::for(SyncEntity::Product, $batch->product),
            'ma_lo'           => $code,
            'ten_lo'          => Str::limit($batch->product->name . ' - ' . $batch->batch_code, 252),
            'ngay_nhap'       => Carbon::parse($importedAt)->toDateString(),
            'ngay_san_xuat'   => $mfg,
            'han_su_dung'     => $batch->exp_date?->toDateString(),
            'dia_chi_thu_mua' => $this->limit($vendor?->address, 2000),
            'ma_ncc_dau_vao'  => $vendorCode,
            'ghi_chu'         => $receipt?->misa_ref_id ? 'Phiếu nhập ' . $receipt->misa_ref_id : null,
            'danh_sach_kho'   => [['ma_kho' => $d['ma_kho']]],
            'danh_sach_anh'   => $images,
            'danh_sach_khau'  => [$this->clean([
                'ma_buoc_sx'     => Str::limit($code . '-NK', 255, ''),
                'ma_khau'        => $d['ma_khau'],
                'thu_tu'         => 1,
                'ma_co_so'       => $d['ma_co_so'],
                'ma_ncc_dau_vao' => $vendorCode,
                'ma_lo_nhap'     => $this->limit($batch->batch_code, 255),
                'thoi_gian'      => Carbon::parse($importedAt)->format('Y-m-d H:i:s'),
            ])],
        ]);

        return ['requests' => [['path' => '/api/supplier/batches/merge', 'records' => [$record]]], 'errors' => $errors];
    }

    public static function publicFileUrl(?string $url): bool
    {
        if (blank($url) || ! preg_match(self::IMAGE_PATTERN, $url)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === 'localhost' || ! str_contains($host, '.')) {
            return false;
        }

        return filter_var($host, FILTER_VALIDATE_IP) === false
            || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    public static function foodCategoryId(?Product $product): ?string
    {
        $map = (array) config('services.hanoicheck.defaults.category_map', []);
        $code = $product?->category?->code;
        $id = $code !== null && isset($map[$code]) ? $map[$code] : config('services.hanoicheck.defaults.ma_loai_sp');

        return filled($id) ? (string) $id : null;
    }

    private function withValidRange(array $row, string $startKey): array
    {
        if ($row[$startKey] !== null && $row['ngay_het_han'] !== null && $row['ngay_het_han'] <= $row[$startKey]) {
            $row['ngay_het_han'] = null;
        }

        return $this->clean($row);
    }

    private function clean(array $row): array
    {
        return array_filter($row, fn ($v) => $v !== null && $v !== '');
    }

    private function limit(?string $value, int $max): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === null || $value === '' ? null : mb_substr($value, 0, $max);
    }
}
