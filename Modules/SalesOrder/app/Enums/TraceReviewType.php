<?php

namespace Modules\SalesOrder\Enums;

enum TraceReviewType: string
{
    case Rating = 'rating';
    case Issue  = 'issue';

    public function label(): string
    {
        return match ($this) {
            self::Rating => 'Đánh giá',
            self::Issue  => 'Báo sự cố',
        };
    }
}
