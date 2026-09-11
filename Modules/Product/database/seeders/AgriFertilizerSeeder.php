<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Product\Models\AgriFertilizer;

class AgriFertilizerSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('datafiles/agri_fertilizers_master_data.json');

        if (!File::exists($path)) {
            $this->command?->warn('  ⚠ Bỏ qua agri_fertilizers — không tìm thấy ' . $path);
            return;
        }

        $records = json_decode(File::get($path), true) ?? [];

        foreach ($records as $record) {
            AgriFertilizer::query()->updateOrCreate(
                ['name' => $record['name']],
                [
                    'category'    => $record['category'] ?? null,
                    'ingredients' => $record['ingredients'] ?? null,
                    'applicant'   => $record['applicant'] ?? null,
                    'is_banned'   => $record['is_banned'] ?? false,
                ]
            );
        }

        $this->command?->info('  ✓ agri_fertilizers seeded: ' . count($records) . ' phân bón (nguồn: Danh mục Phân bón Quốc gia).');
    }
}
