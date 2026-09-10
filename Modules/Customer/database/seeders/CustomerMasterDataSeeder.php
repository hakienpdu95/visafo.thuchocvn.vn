<?php

namespace Modules\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Customer\Enums\CustomerGroup;
use Modules\Customer\Enums\MealModel;
use Modules\Customer\Models\Customer;

class CustomerMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('datafiles/customers_master_data.json');

        if (!File::exists($path)) {
            $this->command?->error("File không tồn tại: $path");
            return;
        }

        $rows = json_decode(File::get($path), true);
        if (!is_array($rows)) {
            $this->command?->error('File JSON không hợp lệ.');
            return;
        }

        $imported = 0;
        $skipped  = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                $skipped['Thiếu trường bắt buộc (name)'] = ($skipped['Thiếu trường bắt buộc (name)'] ?? 0) + 1;
                continue;
            }

            $taxCode = trim((string) ($row['tax_code'] ?? ''));
            $address = trim((string) ($row['address'] ?? ''));

            // Khớp theo tên pháp nhân — customer_code KHÔNG lấy từ file nguồn,
            // để trait HasAutoCode tự sinh mã theo sequence "KH-xxxxxx" khi tạo mới.
            $customer = Customer::query()->firstOrNew(['name' => $name]);
            $customer->tax_code = $taxCode !== '' ? $taxCode : null;
            $customer->address  = $address !== '' ? $address : null;

            if (!$customer->exists) {
                // Dữ liệu import cũ không có phân loại nhóm/mô hình bữa ăn —
                // gán mặc định chung, nhân viên kinh doanh cập nhật lại chính
                // xác sau qua form sửa. Chỉ gán khi tạo mới — không ghi đè
                // phân loại đã được cập nhật thủ công ở các lần seed lại.
                $customer->customer_group = CustomerGroup::TradingCompany->value;
                $customer->meal_model     = MealModel::IngredientSupply->value;
            }

            $customer->save();

            $imported++;
        }

        $this->command?->info("  ✓ customers_master_data.json: $imported khách hàng đã import.");
        foreach ($skipped as $reason => $count) {
            $this->command?->warn("  ⚠ Bỏ qua ($count): $reason");
        }
    }
}
