<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_documents', function (Blueprint $table) {
            $table->string('documentable_type', 50)->nullable()->change();
            $table->ulid('documentable_id')->nullable()->change();
            $table->ulid('document_master_type_id')->nullable()->change();

            $table->string('custom_name', 255)->nullable()->after('document_master_type_id')
                ->comment('Tên tự do — chỉ dùng khi documentable null (tài liệu nội bộ dùng chung)');
            $table->string('custom_category', 30)->nullable()->after('custom_name')
                ->comment('policy | template | training | other — chỉ dùng khi documentable null');
        });
    }

    public function down(): void
    {
        Schema::table('compliance_documents', function (Blueprint $table) {
            $table->dropColumn(['custom_name', 'custom_category']);
            $table->ulid('document_master_type_id')->nullable(false)->change();
            $table->ulid('documentable_id')->nullable(false)->change();
            $table->string('documentable_type', 50)->nullable(false)->change();
        });
    }
};
