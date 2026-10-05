<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tab "Thương hiệu" hiển thị mọi nhóm hồ sơ doanh nghiệp, lọc bằng cờ is_public của loại giấy tờ.
 * Bật các loại mang tính chứng minh năng lực, an toàn để công bố. KHÔNG bật: hồ sơ nhân sự & y tế (dữ liệu cá nhân),
 * sổ sách vận hành, bảng giá, hợp đồng tương tự, giấy ủy quyền — admin tự bật từng loại nếu cần.
 */
return new class extends Migration
{
    private const PUBLIC_CODES = [
        'internal_soil_test',          // Pháp lý sản phẩm & nguồn gốc
        'internal_water_test',
        'internal_flow_diagram',       // Sổ sách vận hành & giám sát
        'internal_capability_profile', // Năng lực thương mại
    ];

    public function up(): void
    {
        DB::table('document_master_types')->whereIn('code', self::PUBLIC_CODES)->update(['is_public' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('document_master_types')->whereIn('code', self::PUBLIC_CODES)->update(['is_public' => false]);
    }
};
