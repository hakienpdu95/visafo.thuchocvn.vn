<?php

namespace Modules\HanoiCheck\Queries;

use App\Shared\Contracts\QueryInterface;

class GetHanoiCheckCatalogQuery implements QueryInterface
{
    public const CATALOGS = [
        'standard-foods' => '/api/supplier/standard-foods',
        'student-groups' => '/api/supplier/student-groups',
        'meal-types'     => '/api/supplier/meal-types',
        'measures'       => '/api/supplier/measures',
        'schools'        => '/api/supplier/schools',
    ];

    public function __construct(
        public readonly string $catalog,
        public readonly ?string $search = null,
    ) {}
}
