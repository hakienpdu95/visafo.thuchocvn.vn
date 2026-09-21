<?php

namespace Modules\FoodInspection\Queries;

use App\Shared\Contracts\QueryInterface;

/** Nguồn món ăn cho Sổ Bước 3 theo khách hàng + ngày + bữa ăn. */
class FindStep3SourceDishesQuery implements QueryInterface
{
    public function __construct(
        public readonly string $customerId,
        public readonly string $date,
        public readonly string $mealTime,
    ) {}
}
