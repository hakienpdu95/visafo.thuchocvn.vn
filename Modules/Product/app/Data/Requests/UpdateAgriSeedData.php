<?php

namespace Modules\Product\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Boolean;
use Spatie\LaravelData\Data;

class UpdateAgriSeedData extends Data
{
    public function __construct(
        #[Boolean]
        public readonly bool $is_banned,
    ) {}
}
