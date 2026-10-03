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
        if (Schema::hasTable('batches')) {
            return;
        }

        Schema::create('batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('inbound_receipt_id')->constrained('inbound_receipts')->restrictOnDelete()->comment('Phiếu nhập kho tạo ra lô này');
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete()->comment('SKU của lô hàng');
            $table->foreignUlid('vendor_id')->constrained('vendors')->restrictOnDelete()->comment('Nhà cung cấp — denormalize từ inbound_receipt để query nhanh');
            $table->string('mfg_batch_number', 100)->nullable()->index()->comment('Mã lô nhà sản xuất — nhập tay từ bao bì, không chuẩn hóa');
            $table->string('internal_batch_code', 50)->unique()->comment('Mã lô nội bộ — hệ thống tự sinh, dùng cho QR/FEFO');
            $table->date('mfg_date')->nullable()->comment('Ngày sản xuất');
            $table->date('exp_date')->index()->comment('Hạn sử dụng — phục vụ logic FEFO');
            $table->unsignedInteger('initial_qty')->comment('Số lượng ban đầu lúc nhập');
            $table->unsignedInteger('current_qty')->comment('Số lượng tồn hiện tại');
            $table->string('status', 20)->default('available')->index()->comment('available | quarantined | recalled | out_of_stock');
            $table->timestamps();
            

            // Indexes
            $table->index(['product_id', 'status'], 'idx_batches_product_status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};