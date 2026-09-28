<?php

namespace Modules\Contract\Observers;

use Modules\Contract\Support\VendorComplianceCache;

class VendorComplianceCacheObserver
{
    public function saved(): void
    {
        VendorComplianceCache::bump();
    }

    public function deleted(): void
    {
        VendorComplianceCache::bump();
    }

    public function restored(): void
    {
        VendorComplianceCache::bump();
    }

    public function forceDeleted(): void
    {
        VendorComplianceCache::bump();
    }
}
