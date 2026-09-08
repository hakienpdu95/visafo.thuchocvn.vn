<?php

namespace Modules\Recall\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Actions\Backend\ResolveRetailItemTagByCodeAction;
use Modules\Warehouse\Models\RetailItemTag;

class LookupTagForAdverseEventAction
{
    use AsAction;

    public function __construct(
        private readonly ResolveRetailItemTagByCodeAction $resolver,
    ) {}

    public function handle(string $code): ?RetailItemTag
    {
        return $this->resolver->handle($code);
    }
}
