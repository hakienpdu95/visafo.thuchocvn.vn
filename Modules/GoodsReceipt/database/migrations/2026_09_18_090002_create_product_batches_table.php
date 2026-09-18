<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_batches')) {
            return;
        }

        Schema::create('product_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('batch_code', 100)->comment('Mã lô tự sinh: {so_phieu}-{ma_hang}, VD: NK9778-105MBV');
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUlid('goods_receipt_item_id')->constrained('goods_receipt_items')->cascadeOnDelete()->comment('Dòng nhập kho đã sinh ra lô này');

            $table->decimal('initial_qty', 14, 3)->comment('Số lượng ban đầu khi nhập kho');
            $table->decimal('current_qty', 14, 3)->comment('Tồn kho hiện tại của lô — trừ lùi khi xuất hàng');

            $table->date('mfg_date')->nullable()->comment('NSX — nhập thủ công sau khi import');
            $table->date('exp_date')->nullable()->comment('HSD — nhập thủ công sau khi import');

            $table->timestamps();
            $table->softDeletes();

            $table->unique('batch_code', 'product_batches_batch_code_unique');
            $table->unique('goods_receipt_item_id', 'product_batches_goods_receipt_item_id_unique');
            $table->index(['product_id', 'current_qty'], 'idx_product_batches_product_stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
