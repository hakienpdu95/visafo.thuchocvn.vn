<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tách địa chỉ / SĐT thành 2 cột riêng để nhập liệu; `supplier_address_phone` giữ lại làm chuỗi dẫn xuất
     * "[Địa chỉ] - [SĐT]" cho bản in PDF/Excel khớp Mẫu số 1.
     */
    public function up(): void
    {
        Schema::table('food_inspection_step1_details', function (Blueprint $table) {
            if (! Schema::hasColumn('food_inspection_step1_details', 'supplier_address')) {
                $table->string('supplier_address', 500)->nullable()->after('vendor_name');
            }
            if (! Schema::hasColumn('food_inspection_step1_details', 'supplier_phone')) {
                $table->string('supplier_phone', 30)->nullable()->after('supplier_address');
            }
        });

        // Dòng cũ chỉ có chuỗi gộp: không tách được đáng tin cậy nên giữ nguyên chuỗi đó ở cột địa chỉ.
        DB::table('food_inspection_step1_details')
            ->whereNull('supplier_address')->whereNotNull('supplier_address_phone')
            ->update(['supplier_address' => DB::raw('supplier_address_phone')]);
    }

    public function down(): void
    {
        Schema::table('food_inspection_step1_details', function (Blueprint $table) {
            foreach (['supplier_phone', 'supplier_address'] as $column) {
                if (Schema::hasColumn('food_inspection_step1_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
