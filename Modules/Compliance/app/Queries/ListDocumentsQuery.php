<?php

namespace Modules\Compliance\Queries;

use App\Shared\Contracts\QueryInterface;

class ListDocumentsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page             = 1,
        public readonly int     $perPage          = 25,
        public readonly string  $sortField        = 'expiration_date',
        public readonly string  $sortDir          = 'desc',
        public readonly ?string $search           = null,
        public readonly ?string $documentableType = null,
        public readonly bool    $expiring         = false,
        public readonly bool    $expired          = false,
    ) {}
}
