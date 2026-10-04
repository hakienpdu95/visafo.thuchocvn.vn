<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tab "Thương hiệu" trên trang truy xuất hiển thị toàn bộ nhóm "Hồ sơ pháp lý cơ sở" của doanh nghiệp
 * → bật công khai cho các loại nội bộ trong nhóm, TRỪ giấy ủy quyền (chứa thông tin cá nhân người được ủy quyền).
 * Admin vẫn bật/tắt từng loại tại Danh mục loại giấy tờ.
 */
return new class extends Migration
{
    private const KEEP_PRIVATE = ['internal_records_authorization'];

    public function up(): void
    {
        DB::table('document_master_types')
            ->where('document_group', 'legal_facility')
            ->whereJsonContains('applicable_to', 'internal')
            ->whereNotIn('code', self::KEEP_PRIVATE)
            ->update(['is_public' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('document_master_types')->where('code', 'facility_commitment')->update(['is_public' => false]);
    }
};
