<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class ManualRefIdGenerator
{
    /** @param class-string<Model> $model */
    public function next(string $model, string $prefix, string $column = 'misa_ref_id'): string
    {
        $prefix = $prefix . '-' . now()->format('ymd') . '-';

        $last = $model::withTrashed()
            ->where($column, 'like', $prefix . '%')
            ->pluck($column)
            ->map(fn (string $ref) => (int) substr($ref, strlen($prefix)))
            ->max() ?? 0;

        do {
            $ref = $prefix . str_pad((string) ++$last, 4, '0', STR_PAD_LEFT);
        } while ($model::withTrashed()->where($column, $ref)->exists());

        return $ref;
    }
}
