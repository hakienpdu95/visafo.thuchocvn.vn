<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('source_group', 10)->nullable()->after('tax_code')
                ->comment('n1 = Tự sản xuất | n2 = Thu gom | n3 = Chợ đầu mối / siêu thị');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->index('source_group', 'idx_vendors_source_group');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('idx_vendors_source_group');
            $table->dropColumn('source_group');
        });
    }
};
