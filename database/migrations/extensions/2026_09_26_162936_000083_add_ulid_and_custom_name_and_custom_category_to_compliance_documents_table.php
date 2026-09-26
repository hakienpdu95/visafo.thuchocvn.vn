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
        Schema::table('compliance_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('compliance_documents', 'ulid')) {
                $table->ulid('ulid')->nullable();
            }
            if (!Schema::hasColumn('compliance_documents', 'custom_name')) {
                $table->string('custom_name', 255)->nullable()->after('ulid')->comment('Tên tự do — chỉ dùng khi documentable null (tài liệu nội bộ dùng chung)');
            }
            if (!Schema::hasColumn('compliance_documents', 'custom_category')) {
                $table->string('custom_category', 30)->nullable()->after('custom_name')->comment('policy | template | training | other — chỉ dùng khi documentable null');
            }
            if (!Schema::hasColumn('compliance_documents', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->after('custom_category')->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compliance_documents', function (Blueprint $table) {
            if (Schema::hasColumn('compliance_documents', 'created_by')) $table->dropForeign(['created_by']);
            $cols = array_filter(['ulid', 'custom_name', 'custom_category', 'created_by'], fn($c) => Schema::hasColumn('compliance_documents', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};