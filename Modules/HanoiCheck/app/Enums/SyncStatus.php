<?php

namespace Modules\HanoiCheck\Enums;

enum SyncStatus: string
{
    case Pending = 'pending';
    case Fake = 'fake';
    case Accepted = 'accepted';
    case Skipped = 'skipped';
    case Invalid = 'invalid';
    case Failed = 'failed';

    public function synced(): bool
    {
        return $this === self::Fake || $this === self::Accepted;
    }
}
