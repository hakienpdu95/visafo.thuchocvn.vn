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
        if (Schema::hasTable('customer_delivery_points')) {
            return;
        }

        Schema::create('customer_delivery_points', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('site_name', 150)->comment('Tên cơ sở/điểm giao hàng');
            $table->string('address', 500)->comment('Địa chỉ giao hàng');
            $table->char('province_code', 2)->nullable();
            $table->foreign('province_code')->references('province_code')->on('provinces')->nullOnDelete();
            $table->char('ward_code', 5)->nullable();
            $table->foreign('ward_code')->references('ward_code')->on('wards')->nullOnDelete();
            $table->string('receiver_name', 100)->nullable()->comment('Người nhận hàng tại điểm giao');
            $table->string('receiver_phone', 20)->nullable()->comment('SĐT người nhận hàng');
            $table->string('note', 255)->nullable();
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_delivery_points');
    }
};