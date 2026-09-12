<?php

namespace Modules\Employee\Models;

use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Employee\Enums\HealthRecordType;
use Modules\Employee\Enums\RecordStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

class Employee extends Model implements HasMedia
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use LogsActivity;
    use HasTenantMedia;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'facebook_url',
        'job_title',
    ];

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
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
