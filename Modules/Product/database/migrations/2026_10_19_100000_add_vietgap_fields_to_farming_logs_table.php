<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('farming_logs', 'material_name')) {
            return;
        }

        Schema::table('farming_logs', function (Blueprint $table) {
            $table->string('material_name', 255)->nullable()->after('vendor_farming_step_id')
                ->comment('Loại vật tư sử dụng (phân bón, thuốc BVTV) — theo biểu mẫu Nhật ký VietGAP');
            $table->string('dosage', 100)->nullable()->after('material_name')
                ->comment('Liều lượng sử dụng (VD: 200kg, 10ml/1 bình)');
            $table->unsignedSmallInteger('isolation_days')->nullable()->after('dosage')
                ->comment('Thời gian cách ly trên bao bì (ngày) — dùng tính safe_harvest_date');
            $table->string('harvest_yield', 100)->nullable()->after('isolation_days')
                ->comment('Sản lượng thu hoạch (VD: 500 kg)');
            $table->string('buyer_name', 255)->nullable()->after('harvest_yield')
                ->comment('Cơ sở tiêu thụ');
            $table->string('operator_name', 150)->nullable()->after('buyer_name')
                ->comment('Người thực hiện');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('farming_logs', 'material_name')) {
            return;
        }

        Schema::table('farming_logs', function (Blueprint $table) {
            $table->dropColumn(['material_name', 'dosage', 'isolation_days', 'harvest_yield', 'buyer_name', 'operator_name']);
        });
    }
};
