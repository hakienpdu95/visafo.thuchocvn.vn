<?php

namespace App\Models;

use App\Enums\AccountType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Auth\Models\SocialAccount;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password',
    'department', 'is_active', 'last_active_at',
    'account_type', 'trust_level',
    'lifecycle_status', 'email_verified_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable, HasRoles, LogsActivity;

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'last_active_at'     => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'account_type'       => AccountType::class,
            'trust_level'        => 'integer',
            'lifecycle_status'   => \App\Enums\AccountLifecycleStatus::class,
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    // ── Identity helpers ─────────────────────────────────────────────

    public function isFree(): bool
    {
        return $this->account_type === AccountType::Free;
    }

    public function isOrgMember(): bool
    {
        return $this->account_type === AccountType::OrgMember;
    }

    public function isSuspended(): bool
    {
        return $this->account_type === AccountType::Suspended;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'department', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => "Tạo tài khoản: {$this->email}",
                'updated' => "Cập nhật tài khoản: {$this->email}",
                'deleted' => "Xóa tài khoản: {$this->email}",
                default   => $event,
            })
            ->useLogName('Auth');
    }
}
