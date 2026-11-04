<?php

namespace Modules\HanoiCheckOrder\Enums;

enum RemoteOrderStatus: string
{
    case ChoXacNhan = 'CHO_XAC_NHAN';
    case TuChoi = 'TU_CHOI';
    case DangChuanBi = 'DANG_CHUAN_BI';
    case DangGiao = 'DANG_GIAO';
    case DaGiao = 'DA_GIAO';
    case GiaoHangThanhCong = 'GIAO_HANG_THANH_CONG';
    case TraHang = 'TRA_HANG';
    case Huy = 'HUY';

    public function label(): string
    {
        return match ($this) {
            self::ChoXacNhan        => 'Chờ xác nhận',
            self::TuChoi            => 'Từ chối',
            self::DangChuanBi       => 'Đang chuẩn bị',
            self::DangGiao          => 'Đang giao',
            self::DaGiao            => 'Đã giao hàng',
            self::GiaoHangThanhCong => 'Giao hàng thành công',
            self::TraHang           => 'Trả hàng',
            self::Huy               => 'Hủy',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::ChoXacNhan        => 'badge-ghost',
            self::DangChuanBi       => 'badge-warning',
            self::DangGiao          => 'badge-info',
            self::DaGiao            => 'badge-primary',
            self::GiaoHangThanhCong => 'badge-success',
            self::TuChoi, self::TraHang, self::Huy => 'badge-error',
        };
    }

    public function rank(): ?int
    {
        return match ($this) {
            self::ChoXacNhan        => 0,
            self::DangChuanBi       => 1,
            self::DangGiao          => 2,
            self::DaGiao            => 3,
            self::GiaoHangThanhCong => 4,
            default                 => null,
        };
    }

    public function final(): bool
    {
        return in_array($this, [self::GiaoHangThanhCong, self::TuChoi, self::TraHang, self::Huy], true);
    }
}
