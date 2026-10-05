<?php

namespace Modules\GoodsReceipt\Enums;

/** Khâu kiểm soát chất lượng nội bộ của VISAFO trên một lô hàng. */
enum QualityCheckStage: string
{
    case Receiving   = 'receiving';
    case Sensory     = 'sensory';
    case PreDispatch = 'pre_dispatch';

    public function label(): string
    {
        return match ($this) {
            self::Receiving   => 'Kiểm tra tiếp nhận',
            self::Sensory     => 'Kiểm tra cảm quan',
            self::PreDispatch => 'Kiểm tra trước xuất',
        };
    }

    /** Khâu nhập tại phiếu nhập kho (gắn lô); pre_dispatch nhập tại đơn bán (gắn dòng đơn). */
    public function isBatchStage(): bool
    {
        return $this !== self::PreDispatch;
    }

    /** @return self[] */
    public static function batchStages(): array
    {
        return [self::Receiving, self::Sensory];
    }
}
