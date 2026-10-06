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
        Schema::table('product_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('product_batches', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
            if (!Schema::hasColumn('product_batches', 'farming_batch_id')) {
                $table->foreignUlid('farming_batch_id')->nullable()->constrained('farming_batches')->nullOnDelete()->after('created_by')->comment('Lô canh tác nguồn (nhật ký đồng ruộng) — trang truy xuất đi theo khóa này, không đoán theo mã lô');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            if (Schema::hasColumn('product_batches', 'created_by')) $table->dropForeign(['created_by']);
            if (Schema::hasColumn('product_batches', 'farming_batch_id')) $table->dropForeign(['farming_batch_id']);
            $cols = array_filter(['created_by', 'farming_batch_id'], fn($c) => Schema::hasColumn('product_batches', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};