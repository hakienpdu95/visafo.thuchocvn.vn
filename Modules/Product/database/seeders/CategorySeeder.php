<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            Category::query()->updateOrCreate(['code' => $definition['code']], $definition);
        }

        $this->command?->info('  ✓ categories seeded: ' . count($this->definitions()) . ' nhóm thực phẩm.');
    }

    private function definitions(): array
    {
        return [
            ['code' => 'fresh_food',               'name' => 'Thực phẩm tươi sống',                         'description' => null, 'is_active' => true],
            ['code' => 'processed_food',            'name' => 'Thực phẩm đã qua chế biến',                    'description' => null, 'is_active' => true],
            ['code' => 'prepackaged_food',          'name' => 'Thực phẩm bao gói sẵn',                        'description' => null, 'is_active' => true],
            ['code' => 'additives_spices',          'name' => 'Phụ gia thực phẩm & Chất hỗ trợ chế biến',     'description' => null, 'is_active' => true],
            ['code' => 'functional_fortified_food', 'name' => 'Thực phẩm chức năng & Tăng cường vi chất',     'description' => null, 'is_active' => true],
            ['code' => 'beverages_water',           'name' => 'Nước khoáng & Đồ uống',                        'description' => null, 'is_active' => true],
        ];
    }
}
