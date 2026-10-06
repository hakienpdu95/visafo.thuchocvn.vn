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
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'delivery_date')) {
                $table->date('delivery_date')->nullable()->after('delivery_address')->comment('Ngày giao hàng');
            }
            if (!Schema::hasColumn('sales_orders', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->after('delivery_date')->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
            if (!Schema::hasIndex('sales_orders', 'sales_orders_delivery_date_index')) {
                $table->index('delivery_date');
            }
            if (!Schema::hasIndex('sales_orders', 'sales_orders_created_at_index')) {
                $table->index('created_at');
            }
            if (!Schema::hasIndex('sales_orders', 'sales_orders_customer_name_index')) {
                $table->index('customer_name');
            }
            if (!Schema::hasColumn('sales_orders', 'delivery_code')) {
                $table->string('delivery_code', 30)->nullable()->unique()->after('created_by')->comment('Mã vận đơn GH-yymmdd-XXXX, sinh khi bấm Xuất kho');
            }
            if (!Schema::hasColumn('sales_orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('delivery_code')->comment('Thời điểm xuất kho');
            }
            if (!Schema::hasColumn('sales_orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('shipped_at')->comment('Thời điểm giao thành công');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'created_by')) $table->dropForeign(['created_by']);
            $cols = array_filter(['delivery_date', 'created_by', 'delivery_code', 'shipped_at', 'delivered_at'], fn($c) => Schema::hasColumn('sales_orders', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};