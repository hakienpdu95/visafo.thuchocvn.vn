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
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('sku', 100)->comment('Mã nội bộ');
            $table->string('barcode', 50)->nullable()->index()->comment('Mã vạch nhà sản xuất');
            $table->string('name', 255)->comment('Tên sản phẩm');
            $table->foreignUlid('brand_id')->nullable()->constrained('brands')->nullOnDelete()->comment('Thương hiệu');
            $table->string('category_type', 30)->index()->comment('food | cosmetic | medical_device | toy_plastic | textile');
            $table->string('unit', 30)->comment('Đơn vị tính');
            $table->string('external_product_id', 100)->nullable()->index()->comment('ID sản phẩm bên hệ thống POS (Sapo/KiotViet)');
            $table->string('status', 20)->default('active')->index()->comment('active | discontinued');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique('sku', 'products_sku_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};