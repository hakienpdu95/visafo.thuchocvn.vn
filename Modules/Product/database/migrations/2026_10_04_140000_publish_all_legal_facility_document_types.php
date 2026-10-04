<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trang truy xuất (tab "Thương hiệu") hiển thị TOÀN BỘ nhóm "Hồ sơ pháp lý cơ sở" của doanh nghiệp. Migration trước chỉ
 * bật các loại seed sẵn → loại admin tự tạo (ISO, VietGAP…) hoặc loại dùng chung với NCC vẫn is_public = false nên bị ẩn.
 * Bật cho mọi loại trong nhóm, trừ loại có thể chứa thông tin cá nhân / không xác định nội dung.
 */
return new class extends Migration
{
    private const KEEP_PRIVATE = ['internal_records_authorization', 'supplier_other'];

    public function up(): void
    {
        DB::table('document_master_types')
            ->where('document_group', 'legal_facility')
            ->whereNotIn('code', self::KEEP_PRIVATE)
            ->update(['is_public' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Không hoàn tác: tắt lại sẽ ẩn hồ sơ đang công khai.
    }
};
