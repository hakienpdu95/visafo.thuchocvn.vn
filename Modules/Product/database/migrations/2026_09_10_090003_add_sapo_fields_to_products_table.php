<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Khôi phục các cột sapo_product_id/sapo_variant_id/image_url — đã có trong
 * Product::$fillable và được Modules/Sapo (SapoProductMapper, webhook job) sử dụng
 * thực tế để đồng bộ 2 chiều với Sapo POS, nhưng bị thiếu trong migration hiện tại
 * của bảng `products` (lệch giữa model và schema — chặn Product::create()/update()
 * dưới Model::shouldBeStrict() vì Eloquent không tìm thấy cột khi log activity).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sapo_product_id')) {
                $table->string('sapo_product_id', 100)->nullable()->index()->after('external_product_id')->comment('ID sản phẩm bên Sapo POS');
            }
            if (!Schema::hasColumn('products', 'sapo_variant_id')) {
                $table->string('sapo_variant_id', 100)->nullable()->index()->after('sapo_product_id')->comment('ID variant bên Sapo POS');
            }
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('sapo_variant_id')->comment('Ảnh đại diện — đồng bộ từ Sapo POS');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $cols = array_filter(['sapo_product_id', 'sapo_variant_id', 'image_url'], fn ($c) => Schema::hasColumn('products', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};
