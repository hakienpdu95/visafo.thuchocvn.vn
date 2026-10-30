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
        if (Schema::hasTable('retail_item_tags')) {
            return;
        }

        Schema::create('retail_item_tags', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('uid', 20)->nullable()->unique()->comment('Token công khai dùng trên URL truy xuất — ngẫu nhiên, chống đoán nhận');
            $table->string('gs1_serial', 20)->nullable()->unique()->comment('Số sê-ri chuẩn GS1 (AI 21) — duy nhất toàn hệ thống');
            $table->unsignedBigInteger('visual_sequence')->nullable()->index()->comment('Số thứ tự in trực quan trên cuộn tem — dải liên tục toàn hệ thống, dùng để Gắn kết theo dải');
            $table->foreignUlid('batch_id')->nullable()->constrained('batches')->restrictOnDelete()->comment('Lô sản xuất — NULL khi tem đang ở trạng thái provisioned (chưa gắn kết)');
            $table->foreignUlid('product_id')->nullable()->constrained('products')->restrictOnDelete()->comment('Denormalize từ batch để query nhanh — NULL khi provisioned');
            $table->unsignedInteger('serial_number')->nullable()->comment('Số thứ tự trong lô — chỉ gán khi Gắn kết (bind)');
            $table->string('qr_code', 150)->unique()->comment('Chuỗi mã QR — cố định tại thời điểm in, không đổi khi Gắn kết');
            $table->string('status', 20)->default('in_stock')->index()->comment('provisioned | in_stock | sold | transferred | damaged | recalled');
            $table->timestamp('sold_at')->nullable()->comment('Thời điểm bán ra');
            $table->foreignUlid('external_order_id')->nullable()->constrained('external_orders')->nullOnDelete()->comment('Đơn hàng POS đã bán tem này — dùng để truy vết ngược khách hàng khi thu hồi');
            $table->timestamps();
            

            // Indexes
            $table->unique(['batch_id', 'serial_number'], 'uq_retail_tag_batch_serial');
            $table->index(['product_id', 'status'], 'idx_retail_tag_product_status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_item_tags');
    }
};