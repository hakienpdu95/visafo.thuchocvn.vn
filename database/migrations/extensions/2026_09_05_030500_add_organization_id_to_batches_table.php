<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('batches', 'organization_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->foreignUlid('organization_id')->nullable()->after('id')
                    ->constrained('organizations')->cascadeOnDelete()
                    ->comment('Tổ chức sở hữu — denormalize để tenant-scope trực tiếp bảng batches');
            });
        }

        DB::statement('
            UPDATE batches
            INNER JOIN products ON products.id = batches.product_id
            SET batches.organization_id = products.organization_id
            WHERE batches.organization_id IS NULL
        ');
    }

    public function down(): void
    {
        if (Schema::hasColumn('batches', 'organization_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('organization_id');
            });
        }
    }
};
