<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sapo_product_id')) {
                $table->string('sapo_product_id', 100)->nullable()->index()->comment('ID sản phẩm bên Sapo POS');
            }
            if (!Schema::hasColumn('products', 'sapo_variant_id')) {
                $table->string('sapo_variant_id', 100)->nullable()->index()->after('sapo_product_id')->comment('ID variant bên Sapo POS');
            }
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('sapo_variant_id')->comment('Ảnh đại diện — đồng bộ từ Sapo POS');
            }
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->foreignUlid('category_id')->nullable()->constrained('categories')->restrictOnDelete()->after('image_url')->comment('NULL = CCDC/vật tư tiêu hao, không thuộc đối tượng quản lý giấy tờ ATTP');
            }
            if (!Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type', 20)->after('category_id')->comment('raw_material | finished_good | trading_good | consumables');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'category_id')) $table->dropForeign(['category_id']);
            $cols = array_filter(['sapo_product_id', 'sapo_variant_id', 'image_url', 'category_id', 'product_type'], fn($c) => Schema::hasColumn('products', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};