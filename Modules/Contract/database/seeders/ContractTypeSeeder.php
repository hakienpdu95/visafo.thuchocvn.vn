<?php

namespace Modules\Contract\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Contract\Models\ContractType;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        ContractType::query()->forceDelete();
        Schema::enableForeignKeyConstraints();

        foreach ($this->definitions() as $definition) {
            ContractType::query()->create($definition);
        }

        $this->command?->info('  ✓ contract_types seeded: ' . count($this->definitions()) . ' loại hợp đồng.');
    }

    private function definitions(): array
    {
        return [
            [
                'code'        => 'framework_agreement',
                'name'        => 'Hợp đồng nguyên tắc cung cấp thực phẩm',
                'description' => 'Hợp đồng khung, thường không có giá trị tổng cố định — các đơn hàng cụ thể phát sinh theo từng lần giao.',
            ],
            [
                'code'        => 'agricultural_offtake',
                'name'        => 'Hợp đồng liên kết / Bao tiêu nông sản',
                'description' => 'Cam kết thu mua sản lượng nông sản theo mùa vụ/kỳ hạn với nhà cung cấp/hợp tác xã.',
            ],
            [
                'code'        => 'spot_purchase',
                'name'        => 'Hợp đồng mua bán theo chuyến / lô',
                'description' => 'Hợp đồng cho một chuyến hàng hoặc lô hàng cụ thể, có giá trị và thời hạn xác định.',
            ],
            [
                'code'        => 'consignment',
                'name'        => 'Hợp đồng ký gửi hàng hóa',
                'description' => 'Nhà cung cấp ký gửi hàng hóa, thanh toán theo thực tế tiêu thụ.',
            ],
        ];
    }
}
