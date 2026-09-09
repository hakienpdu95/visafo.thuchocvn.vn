<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Product\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Category::query()->forceDelete();
        Schema::enableForeignKeyConstraints();

        foreach ($this->definitions() as $definition) {
            Category::query()->create($definition);
        }

        $this->command?->info('  ✓ categories seeded: ' . count($this->definitions()) . ' nhóm thực phẩm.');
    }

    private function definitions(): array
    {
        return [
            ['code' => 'fresh_food',                 'name' => 'Thực phẩm tươi sống'],
            ['code' => 'processed_food',              'name' => 'Thực phẩm đã qua chế biến'],
            ['code' => 'prepackaged_food',            'name' => 'Thực phẩm bao gói sẵn'],
            ['code' => 'additives_spices',            'name' => 'Phụ gia thực phẩm & Chất hỗ trợ chế biến'],
            ['code' => 'functional_fortified_food',   'name' => 'Thực phẩm chức năng & Tăng cường vi chất'],
            ['code' => 'beverages_water',             'name' => 'Nước khoáng & Đồ uống'],
        ];
    }
}
