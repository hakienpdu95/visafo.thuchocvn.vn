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
        if (Schema::hasTable('product_batches')) {
            return;
        }

        Schema::create('product_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('batch_code', 100)->comment('Mã lô tự sinh: {so_phieu}-{ma_hang}, VD: NK9778-105MBV');
            $table->foreignUlid('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete()->comment('Phiếu nhập kho đã sinh ra lô này');
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('initial_qty', 14, 3)->comment('Số lượng ban đầu khi nhập kho');
            $table->decimal('current_qty', 14, 3)->comment('Tồn kho hiện tại của lô — trừ lùi khi xuất hàng');
            $table->date('mfg_date')->nullable()->comment('NSX — nhập thủ công sau khi import');
            $table->date('exp_date')->nullable()->comment('HSD — tự tính = ngày nhập + products.shelf_life_days khi import (nếu sản phẩm có cấu hình), hoặc nhập thủ công');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique('batch_code', 'product_batches_batch_code_unique');
            $table->unique(['product_id', 'goods_receipt_id'], 'product_batches_product_goods_receipt_unique');
            $table->index(['product_id', 'current_qty'], 'idx_product_batches_product_stock');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};