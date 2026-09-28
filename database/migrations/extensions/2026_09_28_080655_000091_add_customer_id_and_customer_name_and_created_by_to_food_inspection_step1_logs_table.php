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
        Schema::table('food_inspection_step1_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('food_inspection_step1_logs', 'customer_id')) {
                $table->foreignUlid('customer_id')->nullable()->constrained('customers')->restrictOnDelete()->comment('Khách hàng / điểm phục vụ mà lô nguyên liệu này dùng cho');
            }
            if (!Schema::hasColumn('food_inspection_step1_logs', 'customer_name')) {
                $table->string('customer_name', 255)->nullable()->after('customer_id')->comment('Snapshot tên cơ sở tại thời điểm kiểm thực (header của sổ)');
            }
            if (!Schema::hasIndex('food_inspection_step1_logs', 'idx_fi_step1_logs_customer')) {
                $table->index('customer_id', 'idx_fi_step1_logs_customer');
            }
            if (!Schema::hasColumn('food_inspection_step1_logs', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->after('customer_name')->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_inspection_step1_logs', function (Blueprint $table) {
            if (Schema::hasColumn('food_inspection_step1_logs', 'customer_id')) $table->dropForeign(['customer_id']);
            if (Schema::hasColumn('food_inspection_step1_logs', 'created_by')) $table->dropForeign(['created_by']);
            $cols = array_filter(['customer_id', 'customer_name', 'created_by'], fn($c) => Schema::hasColumn('food_inspection_step1_logs', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};