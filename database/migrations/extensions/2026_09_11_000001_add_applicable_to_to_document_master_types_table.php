<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            if (!Schema::hasColumn('document_master_types', 'applicable_to')) {
                $table->json('applicable_to')->nullable()->after('document_group')
                    ->comment('Đối tượng áp dụng — mảng gồm vendor | product | partner_product | internal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            if (Schema::hasColumn('document_master_types', 'applicable_to')) {
                $table->dropColumn('applicable_to');
            }
        });
    }
};
