<?php

namespace Modules\Employee\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Employee\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            Department::query()->updateOrCreate(
                ['name' => $definition['name']],
                $definition,
            );
        }

        $this->command?->info('  ✓ departments seeded: ' . count($this->definitions()) . ' phòng ban.');
    }

    private function definitions(): array
    {
        return [
            ['name' => 'Khối Sản xuất', 'is_food_contact' => true],
            ['name' => 'Khối Vận hành', 'is_food_contact' => false],
            ['name' => 'Phòng Giao nhận', 'is_food_contact' => true],
            ['name' => 'Phòng Hành chính', 'is_food_contact' => false],
        ];
    }
}
