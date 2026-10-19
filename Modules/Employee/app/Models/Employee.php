<?php

namespace Modules\Employee\Models;

use App\Traits\HasCreator;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Employee\Enums\Gender;
use Modules\Employee\Enums\HealthRecordType;
use Modules\Employee\Enums\RecordStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

class Employee extends Model implements HasMedia
{
    use HasCreator;

    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use LogsActivity;
    use HasTenantMedia;

    public const ID_CARD_COLLECTIONS = ['id_card_front', 'id_card_back'];

    protected $fillable = [
        'full_name',
        'gender',
        'dob',
        'email',
        'phone',
        'facebook_url',
        'address',
        'job_title',
        'start_date',
        'id_card_number',
        'id_card_issue_date',
        'id_card_issue_place',
    ];

    protected function casts(): array
    {
        return [
            'gender'             => Gender::class,
            'dob'                => 'date',
            'start_date'         => 'date',
            'id_card_issue_date' => 'date',
        ];
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_employee')
            ->using(DepartmentEmployee::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(EmployeeHealthRecord::class);
    }

    public function latestHealthCheck(): HasOne
    {
        return $this->hasOne(EmployeeHealthRecord::class)->ofMany(
            ['issue_date' => 'max'],
            fn ($query) => $query->where('record_type', HealthRecordType::HealthCheck->value),
        );
    }

    public function latestAttpTraining(): HasOne
    {
        return $this->hasOne(EmployeeHealthRecord::class)->ofMany(
            ['issue_date' => 'max'],
            fn ($query) => $query->where('record_type', HealthRecordType::AttpTraining->value),
        );
    }

    public function isFoodContact(): bool
    {
        return $this->departments->contains(fn (Department $d) => $d->is_food_contact);
    }

    public function healthCheckStatus(): RecordStatus
    {
        return $this->recordStatus($this->latestHealthCheck);
    }

    public function attpTrainingStatus(): RecordStatus
    {
        return $this->recordStatus($this->latestAttpTraining);
    }

    private function recordStatus(?EmployeeHealthRecord $record): RecordStatus
    {
        if ($record === null || $record->expiry_date === null) {
            return $record === null ? RecordStatus::Missing : RecordStatus::Valid;
        }

        if ($record->isExpired()) {
            return RecordStatus::Expired;
        }

        if ($record->isExpiringWithinDays(30)) {
            return RecordStatus::Expiring;
        }

        return RecordStatus::Valid;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['id_card_number'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function permissionModule(): string
    {
        return 'employee';
    }
}
