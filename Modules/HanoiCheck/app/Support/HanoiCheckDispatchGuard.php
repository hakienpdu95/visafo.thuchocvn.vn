<?php

namespace Modules\HanoiCheck\Support;

class HanoiCheckDispatchGuard
{
    private array $seen = [];

    public function once(string $key): bool
    {
        if (isset($this->seen[$key])) {
            return false;
        }

        return $this->seen[$key] = true;
    }
}
