<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Mô tả ngắn (nguồn gốc, xuất xứ, điểm nổi bật) hiển thị ở khối đầu trang truy xuất công khai /trace/{code}. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('name')->comment('Mô tả ngắn hiển thị trên trang truy xuất');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
