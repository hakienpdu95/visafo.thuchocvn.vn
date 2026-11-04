<?php

namespace Modules\HanoiCheck\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\HanoiCheck\Enums\SyncEntity;

class HanoiCheckCodes
{
    public const PATTERN = '/^[A-Za-z0-9_-]{1,255}$/';

    public static function valid(?string $code): bool
    {
        return $code !== null && preg_match(self::PATTERN, $code) === 1;
    }

    public static function for(SyncEntity $entity, Model $model): string
    {
        $raw = trim((string) match ($entity) {
            SyncEntity::Vendor       => $model->vendor_code,
            SyncEntity::Product      => $model->sku,
            SyncEntity::ProductBatch => $model->batch_code,
            default                  => $model->getKey(),
        });

        if (self::valid($raw)) {
            return $raw;
        }

        $slug = trim(preg_replace(['/\s+/', '/[^A-Za-z0-9_-]/', '/-{2,}/'], ['-', '', '-'], Str::ascii($raw)), '-');
        $suffix = strtoupper(substr((string) $model->getKey(), -6));

        return $slug === ''
            ? $entity->codePrefix() . '-' . strtoupper((string) $model->getKey())
            : mb_substr($slug, 0, 240) . '-' . $suffix;
    }
}
