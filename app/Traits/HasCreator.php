<?php

namespace App\Traits;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCreator
{
    abstract public function permissionModule(): string;

    public static function bootHasCreator(): void
    {
        static::creating(function ($model): void {
            if ($model->getAttribute('created_by') === null && auth()->id() !== null) {
                $model->setAttribute('created_by', auth()->id());
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCreatedBy(?User $user): bool
    {
        return $user !== null && $this->getAttribute('created_by') !== null
            && (string) $this->getAttribute('created_by') === (string) $user->getKey();
    }

    public function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if ($user === null || ! ModuleAccess::onlyOwn($user, $this->permissionModule())) {
            return $query;
        }

        return $query->where($this->qualifyColumn('created_by'), $user->getKey());
    }
}
