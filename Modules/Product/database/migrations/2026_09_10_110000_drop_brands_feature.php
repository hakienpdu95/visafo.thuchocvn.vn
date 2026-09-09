<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Loại bỏ hoàn toàn tính năng Thương hiệu (Brand) — không còn dùng trong nghiệp vụ
 * chuỗi cung ứng thực phẩm của Visafo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'brand_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('brand_id');
            });
        }

        Schema::dropIfExists('brands');
    }

    public function down(): void
    {
        if (!Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('products', 'brand_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignUlid('brand_id')->nullable()->after('name')->constrained('brands')->nullOnDelete();
            });
        }
    }
};
