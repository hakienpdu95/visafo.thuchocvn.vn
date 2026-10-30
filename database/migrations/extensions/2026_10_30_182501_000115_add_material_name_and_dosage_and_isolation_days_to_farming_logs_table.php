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
        Schema::table('farming_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('farming_logs', 'material_name')) {
                $table->string('material_name', 255)->nullable()->comment('Loại vật tư sử dụng (phân bón, thuốc BVTV) — theo biểu mẫu Nhật ký VietGAP');
            }
            if (!Schema::hasColumn('farming_logs', 'dosage')) {
                $table->string('dosage', 100)->nullable()->after('material_name')->comment('Liều lượng sử dụng (VD: 200kg, 10ml/1 bình)');
            }
            if (!Schema::hasColumn('farming_logs', 'isolation_days')) {
                $table->unsignedSmallInteger('isolation_days')->nullable()->after('dosage')->comment('Thời gian cách ly trên bao bì (ngày) — dùng tính safe_harvest_date');
            }
            if (!Schema::hasColumn('farming_logs', 'harvest_yield')) {
                $table->string('harvest_yield', 100)->nullable()->after('isolation_days')->comment('Sản lượng thu hoạch (VD: 500 kg)');
            }
            if (!Schema::hasColumn('farming_logs', 'buyer_name')) {
                $table->string('buyer_name', 255)->nullable()->after('harvest_yield')->comment('Cơ sở tiêu thụ');
            }
            if (!Schema::hasColumn('farming_logs', 'operator_name')) {
                $table->string('operator_name', 150)->nullable()->after('buyer_name')->comment('Người thực hiện');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farming_logs', function (Blueprint $table) {
            $cols = array_filter(['material_name', 'dosage', 'isolation_days', 'harvest_yield', 'buyer_name', 'operator_name'], fn($c) => Schema::hasColumn('farming_logs', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};