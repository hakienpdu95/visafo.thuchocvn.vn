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
        if (Schema::hasTable('food_inspection_step1_logs')) {
            return;
        }

        Schema::create('food_inspection_step1_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete()->comment('Phiếu nhập kho MISA đã nạp danh sách hàng (nếu có)');
            $table->dateTime('inspected_at')->comment('Thời gian kiểm tra');
            $table->string('inspection_location', 255)->nullable()->comment('Địa điểm kiểm tra (tên bếp ăn / trường học)');
            $table->json('attachments')->nullable()->comment('Ảnh hóa đơn / giấy kiểm dịch dùng chung cả chuyến: [{path, name}]');
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('failed_items_count')->default(0)->comment('Số dòng hàng có cảm quan hoặc test nhanh \"Không đạt\" — tính lúc lưu để lọc nhanh');
            $table->foreignUlid('inspected_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người kiểm thực');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index('inspected_at', 'idx_fi_step1_logs_inspected_at');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('food_inspection_step1_logs');
    }
};