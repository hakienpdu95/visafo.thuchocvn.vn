<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hồ sơ pháp nhân (chỉ dùng cho bản ghi type = headquarter): tên, loại hình, mã định danh + ngày/nơi cấp
 * (phục vụ sinh hợp đồng/văn bản pháp lý), địa chỉ hành chính 2 cấp Tỉnh/TP → Phường/Xã (không còn cấp quận/huyện).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_facilities', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('company_type', 30)->nullable()->after('company_name');
            $table->string('tax_code', 20)->nullable()->after('company_type');
            $table->date('tax_code_issue_date')->nullable()->after('tax_code');
            $table->string('tax_code_issue_place')->nullable()->after('tax_code_issue_date');
            $table->string('province_code', 2)->nullable()->after('tax_code_issue_place');
            $table->string('ward_code', 5)->nullable()->after('province_code');
        });
    }

    public function down(): void
    {
        Schema::table('internal_facilities', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'company_type', 'tax_code', 'tax_code_issue_date', 'tax_code_issue_place', 'province_code', 'ward_code']);
        });
    }
};
