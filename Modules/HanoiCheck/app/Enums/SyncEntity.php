<?php

namespace Modules\HanoiCheck\Enums;

use Illuminate\Database\Eloquent\Model;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Models\Product;
use Modules\SalesOrder\Models\PrintLog;
use Modules\Vendor\Models\Vendor;

enum SyncEntity: string
{
    case Master = 'master';
    case Vendor = 'vendor';
    case Product = 'product';
    case ProductBatch = 'product_batch';
    case GoodsReceipt = 'goods_receipt';
    case PrintLog = 'print_log';

    public function modelClass(): ?string
    {
        return match ($this) {
            self::Master       => null,
            self::Vendor       => Vendor::class,
            self::Product      => Product::class,
            self::ProductBatch => ProductBatch::class,
            self::GoodsReceipt => GoodsReceipt::class,
            self::PrintLog     => PrintLog::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Master       => 'Dữ liệu nền (cơ sở, kho, khâu, quy trình)',
            self::Vendor       => 'Nhà cung ứng đầu vào',
            self::Product      => 'Thực phẩm',
            self::ProductBatch => 'Lô sản xuất',
            self::GoodsReceipt => 'Phiếu nhập kho',
            self::PrintLog     => 'Tem truy xuất',
        };
    }

    public function codePrefix(): ?string
    {
        return match ($this) {
            self::Vendor       => 'NCC',
            self::Product      => 'SP',
            self::ProductBatch => 'LO',
            default            => null,
        };
    }

    public function observed(): bool
    {
        return $this !== self::Master;
    }

    public static function fromModel(Model $model): ?self
    {
        foreach (self::cases() as $case) {
            $class = $case->modelClass();
            if ($class !== null && $model instanceof $class) {
                return $case;
            }
        }

        return null;
    }
}
