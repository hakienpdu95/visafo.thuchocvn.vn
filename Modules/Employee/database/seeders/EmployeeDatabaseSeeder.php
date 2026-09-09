<?php

namespace Modules\Employee\Database\Seeders;

use Illuminate\Database\Seeder;

class EmployeeDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
        ]);
    }
}
