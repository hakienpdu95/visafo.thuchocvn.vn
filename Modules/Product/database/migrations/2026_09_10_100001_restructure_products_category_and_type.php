<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đập bỏ hệ danh mục bán lẻ cũ (category_type: food/cosmetic/medical_device/...) —
 * Visafo chỉ phục vụ chuỗi cung ứng thực phẩm/bếp ăn bán trú, không cần phân loại
 * đa ngành hàng. Thay bằng:
 *   - category_id: FK tới bảng `categories` (6 nhóm thực phẩm chuẩn ATTP, xem CategorySeeder)
 *   - product_type: bản chất hàng hóa — raw_material | finished_good | trading_good
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'category_type')) {
                $table->dropColumn('category_type');
            }

            if (!Schema::hasColumn('products', 'category_id')) {
                $table->foreignUlid('category_id')->nullable()->after('brand_id')->constrained('categories')->restrictOnDelete();
            }

            if (!Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type', 20)->after('category_id')->comment('raw_material | finished_good | trading_good');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'product_type')) {
                $table->dropColumn('product_type');
            }
            if (Schema::hasColumn('products', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }
            if (!Schema::hasColumn('products', 'category_type')) {
                $table->string('category_type', 30)->index()->comment('food | cosmetic | medical_device | toy_plastic | textile');
            }
        });
    }
};
