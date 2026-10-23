<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('vendors', 'supply_groups')) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table) {
            $table->json('supply_groups')->nullable()->after('source_group')
                ->comment('Nhóm hàng cung cấp (nhiều giá trị): produce | fresh_food | dry_goods | processed_food');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vendors', 'supply_groups')) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('supply_groups');
        });
    }
};
