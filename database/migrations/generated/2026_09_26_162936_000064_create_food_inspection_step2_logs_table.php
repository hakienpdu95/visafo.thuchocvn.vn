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
        if (Schema::hasTable('food_inspection_step2_logs')) {
            return;
        }

        Schema::create('food_inspection_step2_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('customer_id')->constrained('customers')->restrictOnDelete()->comment('Cơ sở / doanh nghiệp suất ăn được kiểm thực (Master Data Khách hàng)');
            $table->string('customer_name', 255)->comment('Snapshot tên cơ sở tại thời điểm kiểm thực');
            $table->foreignUlid('delivery_point_id')->nullable()->constrained('customer_delivery_points')->nullOnDelete()->comment('Điểm giao/bếp ăn khớp theo tên địa điểm (nếu có)');
            $table->string('location_name', 255)->nullable()->comment('Địa điểm kiểm thực (tên bếp ăn / trường học)');
            $table->date('inspection_date')->comment('Ngày kiểm tra');
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('failed_items_count')->default(0)->comment('Số món có tiêu chí vệ sinh/cảm quan không đạt — tính lúc lưu để lọc nhanh');
            $table->foreignUlid('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspector_name', 255)->comment('Snapshot tên người kiểm tra');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index('inspection_date', 'idx_fi_step2_logs_date');
            $table->index('customer_id', 'idx_fi_step2_logs_customer');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('food_inspection_step2_logs');
    }
};