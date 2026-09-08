<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('retail_item_tags')) {
            return;
        }

        Schema::create('retail_item_tags', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete()->comment('Tổ chức sở hữu');
            $table->foreignUlid('batch_id')->constrained('batches')->restrictOnDelete()->comment('Lô sản xuất sinh ra tem này');
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete()->comment('Denormalize từ batch để query nhanh');
            $table->unsignedInteger('serial_number')->comment('Số thứ tự trong lô');
            $table->string('qr_code', 150)->unique()->comment('Chuỗi mã QR — SKU + mã lô nội bộ + serial');
            $table->string('status', 20)->default('in_stock')->index()->comment('in_stock | sold | damaged | recalled');
            $table->timestamp('sold_at')->nullable()->comment('Thời điểm bán ra');
            $table->timestamps();

            $table->unique(['batch_id', 'serial_number'], 'uq_retail_tag_batch_serial');
            $table->index(['product_id', 'status'], 'idx_retail_tag_product_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_item_tags');
    }
};
