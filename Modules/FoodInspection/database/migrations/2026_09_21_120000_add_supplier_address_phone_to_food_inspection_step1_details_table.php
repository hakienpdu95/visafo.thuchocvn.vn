<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_inspection_step1_details', function (Blueprint $table) {
            if (! Schema::hasColumn('food_inspection_step1_details', 'supplier_address_phone')) {
                $table->string('supplier_address_phone', 500)->nullable()->after('vendor_name')
                    ->comment('Địa chỉ, điện thoại nơi cung cấp — chuỗi "[Địa chỉ] - [SĐT]" (Cột 6 Mục I / Cột 9 Mục II, Mẫu số 1 QĐ 1246/QĐ-BYT)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_inspection_step1_details', function (Blueprint $table) {
            if (Schema::hasColumn('food_inspection_step1_details', 'supplier_address_phone')) {
                $table->dropColumn('supplier_address_phone');
            }
        });
    }
};
