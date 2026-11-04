<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hanoicheck_order_links')) {
            return;
        }

        Schema::create('hanoicheck_order_links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('sales_order_id', 26)->unique()->comment('Đơn xuất hàng VISAFO (sales_orders.id) — không đặt FK để không ràng buộc phân hệ SalesOrder');
            $table->string('hanoicheck_order_code', 255)->unique()->comment('Mã đơn trên cổng HanoiCheck, dùng cho /api/supplier/orders/{code}');
            $table->string('note', 500)->nullable();
            $table->char('linked_by', 26)->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hanoicheck_order_links');
    }
};
