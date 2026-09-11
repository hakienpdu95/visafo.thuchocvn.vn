<?php

namespace Database\Seeders;

use App\Models\SystemSequence;
use Illuminate\Database\Seeder;

/**
 * Khởi tạo các bộ đếm sinh mã tự động (system_sequences).
 *
 * Dùng firstOrCreate — không được updateOrCreate, tránh reset last_number
 * đang chạy về giá trị mặc định mỗi khi seeder chạy lại.
 */
class SystemSequenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            SystemSequence::query()->firstOrCreate(
                ['code_type' => $definition['code_type']],
                $definition,
            );
        }

        $this->command?->info('  ✓ system_sequences seeded: ' . count($this->definitions()) . ' bộ đếm.');
    }

    private function definitions(): array
    {
        return [
            ['code_type' => 'vendor', 'prefix' => 'NCC', 'last_number' => 0, 'padding_length' => 6],
            ['code_type' => 'employee', 'prefix' => 'NV', 'last_number' => 0, 'padding_length' => 6],
            ['code_type' => 'partner_product', 'prefix' => 'MHCC', 'last_number' => 0, 'padding_length' => 6],
            ['code_type' => 'contract', 'prefix' => 'HĐ', 'last_number' => 0, 'padding_length' => 6],
            ['code_type' => 'customer', 'prefix' => 'KH', 'last_number' => 0, 'padding_length' => 6],
            ['code_type' => 'product', 'prefix' => 'SP', 'last_number' => 0, 'padding_length' => 6],
            ['code_type' => 'category', 'prefix' => 'DMNH', 'last_number' => 0, 'padding_length' => 6],
            ['code_type' => 'document_master_type', 'prefix' => 'LGT', 'last_number' => 0, 'padding_length' => 6],
            ['code_type' => 'farming_source', 'prefix' => 'VT', 'last_number' => 0, 'padding_length' => 6],
            ['code_type' => 'farming_batch', 'prefix' => 'LOT', 'last_number' => 0, 'padding_length' => 6],
        ];
    }
}
