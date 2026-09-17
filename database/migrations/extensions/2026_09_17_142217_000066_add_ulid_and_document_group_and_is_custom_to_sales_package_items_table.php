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
        Schema::table('sales_package_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_package_items', 'ulid')) {
                $table->ulid('ulid')->nullable();
            }
            if (!Schema::hasColumn('sales_package_items', 'document_group')) {
                $table->string('document_group', 30)->nullable()->after('ulid');
            }
            if (!Schema::hasColumn('sales_package_items', 'is_custom')) {
                $table->boolean('is_custom')->default(false)->after('document_group')->comment('true = tài liệu tải lên ngoài hệ thống, không gắn với compliance_documents');
            }
            if (!Schema::hasColumn('sales_package_items', 'custom_name')) {
                $table->string('custom_name', 255)->nullable()->after('is_custom');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_package_items', function (Blueprint $table) {
            $cols = array_filter(['ulid', 'document_group', 'is_custom', 'custom_name'], fn($c) => Schema::hasColumn('sales_package_items', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};