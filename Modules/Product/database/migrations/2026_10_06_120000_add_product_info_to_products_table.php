<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'product_info')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->longText('product_info')->nullable()->after('description')
                ->comment('Thông tin sản phẩm');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'product_info')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropColumn('product_info'));
        }
    }
};
