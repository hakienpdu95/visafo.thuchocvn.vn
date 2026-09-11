<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Support\Facades\File;
use Illuminate\Database\Seeder;
use Modules\Product\Models\AgriSeed;

class AgriSeedSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('datafiles/agri_seeds_master_data.json');

        if (!File::exists($path)) {
            $this->command?->warn('  ⚠ Bỏ qua agri_seeds — không tìm thấy ' . $path);
            return;
        }

        $records = json_decode(File::get($path), true) ?? [];

        foreach ($records as $record) {
            AgriSeed::query()->updateOrCreate(
                [
                    'name'      => $record['name'],
                    'crop_type' => $record['crop_type'] ?? null,
                ],
                [
                    'author_applicant' => $record['author_applicant'] ?? null,
                    'decision_number'  => $record['decision_number'] ?: null,
                    'is_banned'        => $record['is_banned'] ?? false,
                ]
            );
        }

        $this->command?->info('  ✓ agri_seeds seeded: ' . count($records) . ' giống cây trồng (nguồn: Danh mục Giống cây trồng Quốc gia).');
    }
}
