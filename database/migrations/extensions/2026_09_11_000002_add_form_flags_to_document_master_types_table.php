<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            if (!Schema::hasColumn('document_master_types', 'has_expiration_date')) {
                $table->boolean('has_expiration_date')->default(true)->after('is_required_expiry_date');
            }
            if (!Schema::hasColumn('document_master_types', 'has_issue_place')) {
                $table->boolean('has_issue_place')->default(true)->after('has_expiration_date');
            }
            if (!Schema::hasColumn('document_master_types', 'is_transactional')) {
                $table->boolean('is_transactional')->default(false)->after('has_issue_place');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            $cols = array_filter(
                ['has_expiration_date', 'has_issue_place', 'is_transactional'],
                fn ($c) => Schema::hasColumn('document_master_types', $c)
            );
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};
