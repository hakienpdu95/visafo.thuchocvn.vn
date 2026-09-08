<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'sapo_product_id')) {
                $table->string('sapo_product_id', 100)->nullable()->after('external_product_id')->comment('ID Product bên Sapo (1 product có nhiều variant)');
            }
            if (! Schema::hasColumn('products', 'sapo_variant_id')) {
                $table->string('sapo_variant_id', 100)->nullable()->after('sapo_product_id')->comment('ID Variant bên Sapo — map 1-1 với 1 dòng products');
            }
            if (! Schema::hasColumn('products', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('sapo_variant_id')->comment('Ảnh đại diện lấy từ Sapo Product');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['organization_id', 'sapo_variant_id'], 'uq_products_org_sapo_variant');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('uq_products_org_sapo_variant');
            $table->dropColumn(['sapo_product_id', 'sapo_variant_id', 'image_url']);
        });
    }
};
