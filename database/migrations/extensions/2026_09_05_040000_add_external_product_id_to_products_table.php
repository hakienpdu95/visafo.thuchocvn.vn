<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'external_product_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('external_product_id', 100)->nullable()->after('unit')->index()
                    ->comment('ID sản phẩm bên hệ thống POS (Sapo/KiotViet)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'external_product_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('external_product_id');
            });
        }
    }
};
