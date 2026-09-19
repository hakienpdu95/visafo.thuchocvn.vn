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
        if (Schema::hasTable('goods_receipt_items')) {
            return;
        }

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete()->comment('Phiếu nhập kho chứa dòng này');
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete()->comment('Map từ cột \"Mã số\" trong Excel');
            $table->unsignedSmallInteger('line_no')->nullable()->comment('STT của dòng trong phiếu Excel gốc');
            $table->string('product_name_raw', 255)->nullable()->comment('Tên hàng nguyên văn trong Excel');
            $table->string('unit_raw', 50)->nullable()->comment('Đơn vị tính nguyên văn trong Excel');
            $table->decimal('quantity', 14, 3)->comment('Số lượng theo chứng từ');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['goods_receipt_id', 'product_id'], 'idx_goods_receipt_items_receipt_product');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};