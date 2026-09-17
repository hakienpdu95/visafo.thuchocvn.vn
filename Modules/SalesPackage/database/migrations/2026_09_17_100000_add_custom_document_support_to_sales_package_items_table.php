<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_package_items', function (Blueprint $table) {
            $table->ulid('compliance_document_id')->nullable()->change();
            $table->string('document_group', 30)->nullable()->change();
            $table->boolean('is_custom')->default(false)->after('is_valid')
                ->comment('true = tài liệu tải lên ngoài hệ thống, không gắn với compliance_documents');
            $table->string('custom_name', 255)->nullable()->after('is_custom');
        });
    }

    public function down(): void
    {
        Schema::table('sales_package_items', function (Blueprint $table) {
            $table->dropColumn(['is_custom', 'custom_name']);
            $table->string('document_group', 30)->nullable(false)->change();
            $table->ulid('compliance_document_id')->nullable(false)->change();
        });
    }
};
