<?php

namespace Modules\HanoiCheck\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\HanoiCheck\Enums\SyncEntity;

class GetHanoiCheckSyncStateQuery implements QueryInterface
{
    public function __construct(
        public readonly SyncEntity $entity,
        public readonly string $localId,
        public readonly string $environment,
        public readonly ?string $endpoint = null,
    ) {}
}
