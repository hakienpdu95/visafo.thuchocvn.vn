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
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'type')) {
                $table->string('type', 20)->default('input');
            }
            if (!Schema::hasColumn('contracts', 'customer_id')) {
                $table->foreignUlid('customer_id')->nullable()->constrained('customers')->restrictOnDelete()->after('type');
            }
            if (!Schema::hasIndex('contracts', 'idx_contracts_type')) {
                $table->index('type', 'idx_contracts_type');
            }
            if (!Schema::hasColumn('contracts', 'ulid')) {
                $table->ulid('ulid')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('contracts', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->after('ulid')->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'customer_id')) $table->dropForeign(['customer_id']);
            if (Schema::hasColumn('contracts', 'created_by')) $table->dropForeign(['created_by']);
            $cols = array_filter(['type', 'customer_id', 'ulid', 'created_by'], fn($c) => Schema::hasColumn('contracts', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};