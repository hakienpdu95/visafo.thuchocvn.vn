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
        Schema::table('internal_facilities', function (Blueprint $table) {
            if (!Schema::hasColumn('internal_facilities', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
            if (!Schema::hasColumn('internal_facilities', 'company_name')) {
                $table->string('company_name')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('internal_facilities', 'company_type')) {
                $table->string('company_type', 30)->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('internal_facilities', 'tax_code')) {
                $table->string('tax_code', 20)->nullable()->after('company_type');
            }
            if (!Schema::hasColumn('internal_facilities', 'tax_code_issue_date')) {
                $table->date('tax_code_issue_date')->nullable()->after('tax_code');
            }
            if (!Schema::hasColumn('internal_facilities', 'tax_code_issue_place')) {
                $table->string('tax_code_issue_place')->nullable()->after('tax_code_issue_date');
            }
            if (!Schema::hasColumn('internal_facilities', 'province_code')) {
                $table->string('province_code', 2)->nullable()->after('tax_code_issue_place');
            }
            if (!Schema::hasColumn('internal_facilities', 'ward_code')) {
                $table->string('ward_code', 5)->nullable()->after('province_code');
            }
            if (!Schema::hasColumn('internal_facilities', 'supply_chain_role')) {
                $table->longText('supply_chain_role')->nullable()->after('ward_code')->comment('Vai trò trong chuỗi cung ứng (HTML từ Jodit, đã làm sạch) — hiển thị ở khối \"Đơn vị cung ứng\" trang truy xuất');
            }
        });
    }

    public function down(): void
    {
        Schema::table('internal_facilities', function (Blueprint $table) {
            if (Schema::hasColumn('internal_facilities', 'created_by')) $table->dropForeign(['created_by']);
            $cols = array_filter(['created_by', 'company_name', 'company_type', 'tax_code', 'tax_code_issue_date', 'tax_code_issue_place', 'province_code', 'ward_code', 'supply_chain_role'], fn($c) => Schema::hasColumn('internal_facilities', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};