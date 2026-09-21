<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryInterface;

/** Nguồn món ăn cần lưu mẫu theo khách hàng + ngày + bữa ăn. */
class FindSampleSourceDishesQuery implements QueryInterface
{
    public function __construct(
        public readonly string $customerId,
        public readonly string $date,
        public readonly string $mealTime,
    ) {}
}
