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
            $cols = array_filter(['internal_tab_group'], fn($c) => Schema::hasColumn('document_master_types', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }

    public function down(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            // TODO: $table->string('internal_tab_group')->...; // add lại 'internal_tab_group'
        });
    }
};