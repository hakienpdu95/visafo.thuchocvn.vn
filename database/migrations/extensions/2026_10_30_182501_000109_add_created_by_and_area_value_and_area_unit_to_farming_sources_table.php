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
        Schema::table('farming_sources', function (Blueprint $table) {
            if (!Schema::hasColumn('farming_sources', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
            if (!Schema::hasColumn('farming_sources', 'area_value')) {
                $table->decimal('area_value', 12, 2)->nullable()->after('created_by')->comment('Diện tích theo đơn vị người dùng nhập (VD: 2)');
            }
            if (!Schema::hasColumn('farming_sources', 'area_unit')) {
                $table->string('area_unit', 20)->nullable()->after('area_value')->comment('sao_bac_bo | sao_trung_bo | m2 | ha');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farming_sources', function (Blueprint $table) {
            if (Schema::hasColumn('farming_sources', 'created_by')) $table->dropForeign(['created_by']);
            $cols = array_filter(['created_by', 'area_value', 'area_unit'], fn($c) => Schema::hasColumn('farming_sources', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};