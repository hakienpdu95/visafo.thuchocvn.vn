<?php

namespace Modules\Compliance\Queries;

use App\Shared\Contracts\QueryInterface;

class ListWarningsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly ?string $status    = null,
        public readonly ?string $category  = null,
        public readonly ?string $severity  = null,
    ) {}
}
