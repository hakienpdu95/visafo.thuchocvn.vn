<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_orders')) {
            return;
        }

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('misa_ref_id', 50)->comment('Số phiếu xuất kho MISA (VD: XK160195) — chốt chặn chống import trùng');
            $table->string('customer_name', 255)->nullable()->comment('Họ tên người nhận hàng nguyên văn từ Excel');
            $table->text('delivery_address')->nullable()->comment('Địa chỉ (bộ phận) nguyên văn từ Excel');
            $table->string('status', 30)->default('pending');
            $table->string('source_file_name', 255)->nullable()->comment('Tên file Excel gốc đã import');
            $table->foreignUlid('imported_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('misa_ref_id', 'sales_orders_misa_ref_id_unique');
            $table->index('status', 'idx_sales_orders_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
