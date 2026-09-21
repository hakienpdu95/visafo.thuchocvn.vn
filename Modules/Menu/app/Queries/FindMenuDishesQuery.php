<?php

namespace Modules\Menu\Queries;

use App\Shared\Contracts\QueryInterface;

/** Tra món ăn của thực đơn theo cơ sở + ngày + bữa (dùng để đồng bộ xuống Sổ kiểm thực Bước 2). */
class FindMenuDishesQuery implements QueryInterface
{
    public function __construct(
        public readonly string $customerId,
        public readonly string $date,
        public readonly string $mealTime,
    ) {}
}
