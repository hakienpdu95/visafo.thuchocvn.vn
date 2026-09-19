<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'shelf_life_days')) {
                $table->unsignedInteger('shelf_life_days')->nullable()->after('unit')
                    ->comment('Số ngày bảo quản mặc định để tự động tính HSD');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'shelf_life_days')) {
                $table->dropColumn('shelf_life_days');
            }
        });
    }
};
