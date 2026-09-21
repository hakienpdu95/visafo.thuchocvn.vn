<?php

namespace Modules\LabelTemplate\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\LabelTemplate\Models\LabelTemplate;

class LabelTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = $this->definitions();

        foreach ($definitions as $definition) {
            LabelTemplate::query()->updateOrCreate(
                ['view_path' => $definition['view_path']],
                $definition,
            );
        }

        $this->command?->info('  ✓ label templates seeded: ' . count($definitions) . ' mẫu tem 60x40.');
    }

    /** @return array<int, array{name: string, view_path: string, description: string, default_size: string}> */
    private function definitions(): array
    {
        return [
            [
                'name'         => 'Tem Rau Củ Quả',
                'view_path'    => 'labels.templates.produce_60x40',
                'description'  => 'Mẫu cơ bản cho rau củ quả — không có logo chứng nhận hữu cơ/VietGAP (xem thêm Tem Rau Củ Quả Hữu Cơ).',
                'default_size' => '60x40',
            ],
            [
                'name'         => 'Tem Rau Củ Quả Hữu Cơ (VietGAP/Organic)',
                'view_path'    => 'labels.templates.produce_organic_60x40',
                'description'  => 'Mẫu chuẩn cho rau củ, có không gian in logo chứng nhận hữu cơ/VietGAP.',
                'default_size' => '60x40',
            ],
            [
                'name'         => 'Tem Thịt Tươi Sống (Heo/Bò/Gà)',
                'view_path'    => 'labels.templates.meat_fresh_60x40',
                'description'  => 'Bổ sung trường thông tin Ngày giết mổ, điều kiện bảo quản mát và mã kiểm dịch thú y.',
                'default_size' => '60x40',
            ],
            [
                'name'         => 'Tem Hải Sản Đông Lạnh',
                'view_path'    => 'labels.templates.seafood_frozen_60x40',
                'description'  => 'Có cảnh báo bảo quản -18°C và thông tin rã đông.',
                'default_size' => '60x40',
            ],
            [
                'name'         => 'Tem Trái Cây Nhập Khẩu',
                'view_path'    => 'labels.templates.fruits_imported_60x40',
                'description'  => 'Nhấn mạnh thông tin Xuất xứ (Country of Origin) và Đơn vị nhập khẩu.',
                'default_size' => '60x40',
            ],
            [
                'name'         => 'Tem Thực Phẩm Sơ Chế / Chế Biến Sẵn',
                'view_path'    => 'labels.templates.pre_cooked_60x40',
                'description'  => 'Có không gian hiển thị Thành phần chính và Hướng dẫn nấu nướng (HDSD).',
                'default_size' => '60x40',
            ],
            [
                'name'         => 'Tem Nông Sản Khô / Ngũ Cốc',
                'view_path'    => 'labels.templates.dried_food_60x40',
                'description'  => 'Phù hợp hàng lưu kho lâu ngày, cảnh báo độ ẩm.',
                'default_size' => '60x40',
            ],
        ];
    }
}
