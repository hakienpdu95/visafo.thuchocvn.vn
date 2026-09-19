<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_order_items')) {
            return;
        }

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete()->comment('Map từ cột "Mã số" trong Excel');

            $table->unsignedSmallInteger('line_no')->nullable()->comment('STT của dòng đầu tiên của mã hàng này trong Excel gốc');
            $table->string('product_name_raw', 255)->nullable()->comment('Tên hàng nguyên văn trong Excel');
            $table->string('unit_raw', 50)->nullable()->comment('Đơn vị tính nguyên văn trong Excel');
            $table->decimal('requested_qty', 14, 3)->comment('Số lượng yêu cầu (cột "Yêu cầu"), đã gộp theo mã hàng');
            $table->decimal('actual_qty', 14, 3)->nullable()->comment('Thực xuất — nhân viên kho cân và điền sau');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_id', 'product_id'], 'idx_sales_order_items_order_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
