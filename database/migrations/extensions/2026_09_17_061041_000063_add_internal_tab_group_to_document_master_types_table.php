<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            if (!Schema::hasColumn('document_master_types', 'internal_tab_group')) {
                $table->string('internal_tab_group', 20)->nullable()->comment('legal | operation | hr — chỉ dùng khi applicable_to chứa internal, quyết định tab hiển thị ở trang Hồ sơ năng lực VISAFO');
            }
            if (!Schema::hasIndex('document_master_types', 'idx_document_master_types_internal_tab_group')) {
                $table->index('internal_tab_group', 'idx_document_master_types_internal_tab_group');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            $cols = array_filter(['internal_tab_group'], fn($c) => Schema::hasColumn('document_master_types', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};