<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Product\Models\AgriPesticide;

class AgriPesticideSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('datafiles/agri_pesticides_master_data.json');

        if (!File::exists($path)) {
            $this->command?->warn('  ⚠ Bỏ qua agri_pesticides — không tìm thấy ' . $path);
            return;
        }

        $records = json_decode(File::get($path), true) ?? [];

        foreach ($records as $record) {
            AgriPesticide::query()->updateOrCreate(
                ['trade_name' => $record['trade_name']],
                [
                    'category'            => $record['category'] ?? null,
                    'active_ingredients'  => $record['active_ingredients'] ?? null,
                    'target_pest'         => $record['target_pest'] ?? null,
                    'applicant'           => $record['applicant'] ?? null,
                    'quarantine_days'     => $record['quarantine_days'] ?? null,
                    'is_banned'           => $record['is_banned'] ?? false,
                ]
            );
        }

        $this->command?->info('  ✓ agri_pesticides seeded: ' . count($records) . ' thuốc BVTV (nguồn: Bộ NN&PTNT).');
    }
}
