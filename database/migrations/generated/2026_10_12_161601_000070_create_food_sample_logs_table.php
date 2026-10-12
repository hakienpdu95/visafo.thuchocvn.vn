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
        if (Schema::hasTable('food_sample_logs')) {
            return;
        }

        Schema::create('food_sample_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('customer_id')->constrained('customers')->restrictOnDelete()->comment('Khách hàng / điểm phục vụ — cô lập dữ liệu, khớp Thực đơn và Sổ Bước 1-3');
            $table->string('customer_name', 255)->comment('Snapshot tên cơ sở tại thời điểm lưu mẫu');
            $table->foreignUlid('delivery_point_id')->nullable()->constrained('customer_delivery_points')->nullOnDelete();
            $table->string('location_name', 255)->nullable()->comment('Địa điểm lưu mẫu (bếp ăn)');
            $table->date('sample_date')->comment('Ngày lưu mẫu (ngày của bữa ăn)');
            $table->string('status', 20)->default('stored')->comment('stored = Lưu mẫu | destroyed = Đã hủy (tất cả mẫu đã hủy)');
            $table->text('note')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('creator_name', 255)->comment('Snapshot tên người lập phiếu');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index('sample_date', 'idx_food_sample_logs_date');
            $table->index('customer_id', 'idx_food_sample_logs_customer');
            $table->index('status', 'idx_food_sample_logs_status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('food_sample_logs');
    }
};