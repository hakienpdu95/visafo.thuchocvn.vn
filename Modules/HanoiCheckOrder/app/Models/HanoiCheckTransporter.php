<?php

namespace Modules\HanoiCheckOrder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class HanoiCheckTransporter extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $table = 'hanoicheck_transporters';

    protected $fillable = [
        'code', 'name', 'phone', 'cccd', 'date_of_birth', 'address', 'transport_mean', 'license_plate', 'employee_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function toHanoiCheckRecord(): array
    {
        return array_filter([
            'ma_nhan_su'         => $this->code,
            'ho_ten'             => mb_substr($this->name, 0, 255),
            'la_chu_co_so'       => false,
            'la_nguoi_che_bien'  => false,
            'la_nguoi_giao_hang' => true,
            'trang_thai'         => (bool) $this->is_active,
            'vi_tri'             => 'Nhân viên giao hàng',
            'ngay_sinh'          => $this->date_of_birth?->toDateString(),
            'dia_chi'            => $this->address ? mb_substr($this->address, 0, 255) : null,
            'dien_thoai'         => $this->phone ? mb_substr($this->phone, 0, 20) : null,
            'cccd'               => $this->cccd ? mb_substr($this->cccd, 0, 20) : null,
            'phuong_tien'        => $this->transport_mean ? mb_substr($this->transport_mean, 0, 255) : null,
            'bien_so'            => mb_substr($this->license_plate, 0, 255),
        ], fn ($v) => $v !== null);
    }
}
