<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gắn Sổ Bước 1 với khách hàng / điểm phục vụ (bếp ăn) để truy xuất xuyên suốt sang Bước 2, Bước 3.
     * Nullable vì các sổ tạo trước đây chưa có thông tin này; form tạo/sửa bắt buộc chọn.
     */
    public function up(): void
    {
        Schema::table('food_inspection_step1_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('food_inspection_step1_logs', 'customer_id')) {
                $table->foreignUlid('customer_id')->nullable()->after('id')->constrained('customers')->restrictOnDelete()
                    ->comment('Khách hàng / điểm phục vụ mà lô nguyên liệu này dùng cho');
                $table->string('customer_name', 255)->nullable()->after('customer_id')
                    ->comment('Snapshot tên cơ sở tại thời điểm kiểm thực (header của sổ)');
                $table->index('customer_id', 'idx_fi_step1_logs_customer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_inspection_step1_logs', function (Blueprint $table) {
            if (Schema::hasColumn('food_inspection_step1_logs', 'customer_id')) {
                $table->dropConstrainedForeignId('customer_id');
                $table->dropColumn('customer_name');
            }
        });
    }
};
