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

        $this->command?->info('  ✓ label templates seeded: ' . count($definitions) . ' mẫu tem.');
    }

    /** @return array<int, array{name: string, view_path: string, description: string, default_size: string}> */
    private function definitions(): array
    {
        return [
            [
                'name'         => 'Mẫu tem truy xuất VISAFO - Cỡ lớn (Khổ giấy in nhãn 100x75mm)',
                'view_path'    => 'labels.templates.visafo_100x75',
                'description'  => 'Mẫu tem khổ lớn 100x75mm thương hiệu VISAFO — header công ty, khối sản phẩm nền đen, bảng thông tin + QR, footer bảo quản/liên hệ.',
                'default_size' => '100x75',
            ],
            [
                'name'         => 'Mẫu tem truy xuất VISAFO - Cỡ vừa (Khổ giấy in nhãn 75x50mm)',
                'view_path'    => 'labels.templates.visafo_75x50',
                'description'  => 'Bản thu gọn của mẫu 100x75mm cho cuộn nhãn 75x50mm — header 1 dòng, tên sản phẩm tối đa 2 dòng, thông tin 1 dòng/trường + QR 12mm, footer bảo quản/liên hệ gộp 1 dòng.',
                'default_size' => '75x50',
            ],
        ];
    }
}
