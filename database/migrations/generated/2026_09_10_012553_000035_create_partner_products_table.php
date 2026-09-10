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
        if (Schema::hasTable('partner_products')) {
            return;
        }

        Schema::create('partner_products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->ulid('ulid');
            $table->foreignUlid('vendor_id')->constrained('vendors')->restrictOnDelete()->comment('NCC kê khai mặt hàng này');
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete()->comment('Ánh xạ tới danh mục chuẩn (master catalog) của Visafo');
            $table->string('vendor_sku', 100)->nullable()->comment('Mã hàng nội bộ do NCC tự đặt');
            $table->string('name', 255)->comment('Tên hàng do NCC tự kê khai — VD: Nạc mông, Ba chỉ');
            $table->string('manufacturer_name', 255)->nullable()->comment('Nhà sản xuất / nguồn gốc thực tế nếu mua qua trung gian — VD: CP Việt Nam');
            $table->string('origin_address', 500)->nullable()->comment('Địa chỉ / vùng trồng / lò mổ gốc — phục vụ truy xuất nguồn gốc theo HD-02/HD-BCĐ');
            $table->string('status', 20)->default('active')->index()->comment('active | inactive');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['vendor_id', 'product_id'], 'idx_partner_products_vendor_product');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_products');
    }
};