<?php

namespace Modules\HanoiCheckOrder\Queries;

use App\Shared\Contracts\QueryInterface;

class GetRemoteHanoiCheckOrderQuery implements QueryInterface
{
    public function __construct(public readonly string $code) {}
}
