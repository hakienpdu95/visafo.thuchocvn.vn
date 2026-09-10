<?php

namespace Modules\Vendor\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Vendor\Models\Vendor;

class VendorMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('datafiles/vendors_master_data.json');

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

            // vendor_code KHÔNG lấy từ file nguồn — để trait HasAutoCode tự sinh
            // mã theo sequence "NCC-xxxxxx" khi tạo mới. Mã gốc trong file (nếu
            // có) chỉ dùng làm hậu tố cho placeholder MST bên dưới, không lưu
            // vào cột vendor_code.
            $sourceCode = trim((string) ($row['vendor_code'] ?? ''));

            $taxCode = trim((string) ($row['tax_code'] ?? ''));
            if ($taxCode === '') {
                // tax_code là NOT NULL + unique trong DB — nhà cung cấp chưa có MST
                // thật thì gán placeholder duy nhất, dễ nhận biết để nhân viên
                // cung ứng bổ sung MST thật sau.
                $taxCode = 'CHUA-CAP-' . ($sourceCode !== '' ? $sourceCode : md5($name));
            }

            $address = trim((string) ($row['address'] ?? ''));

            // Khớp theo tên nhà cung cấp — chạy lại nhiều lần không tạo trùng,
            // không đụng tới mã NCC đã được HasAutoCode sinh ra ở lần tạo đầu.
            $vendor = Vendor::query()->firstOrNew(['name' => $name]);
            $vendor->tax_code = $taxCode;
            $vendor->address  = $address !== '' ? $address : null;
            $vendor->save();

            $imported++;
        }

        $this->command?->info("  ✓ vendors_master_data.json: $imported nhà cung cấp đã import.");
        foreach ($skipped as $reason => $count) {
            $this->command?->warn("  ⚠ Bỏ qua ($count): $reason");
        }
    }
}
