<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agri_pesticides')) {
            return;
        }

        Schema::create('agri_pesticides', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('category', 100)->index()->comment('Nhóm thuốc (VD: Thuốc trừ sâu, Thuốc trừ bệnh, Thuốc trừ cỏ)');
            $table->string('active_ingredients', 500)->comment('Thành phần hoạt chất (COMMON NAME)');
            $table->string('trade_name', 255)->index()->comment('Tên thương phẩm (TRADE NAME)');
            $table->string('target_pest', 500)->nullable()->comment('Đối tượng phòng trừ (PEST/CROP)');
            $table->string('applicant', 255)->nullable()->comment('Tổ chức đăng ký (APPLICANT)');
            $table->unsignedInteger('quarantine_days')->nullable()->comment('Thời gian cách ly (ngày) - Data cực quan trọng để chặn thu hoạch');
            $table->boolean('is_banned')->default(false)->comment('Cờ đánh dấu thuốc đã bị NN cấm lưu hành');
            $table->string('status', 20)->default('active')->index()->comment('active | inactive');
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('agri_pesticides');
    }
};