<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'label_template_id')) {
                $table->foreignUlid('label_template_id')->nullable()->after('shelf_life_days')
                    ->constrained('label_templates')->nullOnDelete()
                    ->comment('Mẫu tem in cho sản phẩm — NULL = dùng tem mặc định của hệ thống');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'label_template_id')) {
                $table->dropConstrainedForeignId('label_template_id');
            }
        });
    }
};
