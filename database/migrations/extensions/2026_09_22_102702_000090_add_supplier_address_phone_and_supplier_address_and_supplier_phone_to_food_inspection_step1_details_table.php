<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('food_inspection_step1_details', function (Blueprint $table) {
            if (!Schema::hasColumn('food_inspection_step1_details', 'supplier_address_phone')) {
                $table->string('supplier_address_phone', 500)->nullable()->comment('Địa chỉ, điện thoại nơi cung cấp — chuỗi \"[Địa chỉ] - [SĐT]\" (Cột 6 Mục I / Cột 9 Mục II, Mẫu số 1 QĐ 1246/QĐ-BYT)');
            }
            if (!Schema::hasColumn('food_inspection_step1_details', 'supplier_address')) {
                $table->string('supplier_address', 500)->nullable()->after('supplier_address_phone');
            }
            if (!Schema::hasColumn('food_inspection_step1_details', 'supplier_phone')) {
                $table->string('supplier_phone', 30)->nullable()->after('supplier_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_inspection_step1_details', function (Blueprint $table) {
            $cols = array_filter(['supplier_address_phone', 'supplier_address', 'supplier_phone'], fn($c) => Schema::hasColumn('food_inspection_step1_details', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};