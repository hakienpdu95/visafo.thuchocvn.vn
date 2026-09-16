<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            $table->string('internal_tab_group', 20)->nullable()->after('document_group')
                ->comment('legal | operation | hr — chỉ dùng khi applicable_to chứa internal, quyết định tab hiển thị ở trang Hồ sơ năng lực VISAFO');
        });

        Schema::table('document_master_types', function (Blueprint $table) {
            $table->index('internal_tab_group', 'idx_document_master_types_internal_tab_group');
        });
    }

    public function down(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            $table->dropIndex('idx_document_master_types_internal_tab_group');
            $table->dropColumn('internal_tab_group');
        });
    }
};
