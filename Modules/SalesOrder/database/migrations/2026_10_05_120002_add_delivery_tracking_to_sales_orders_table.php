<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sales_orders', 'delivery_code')) {
            return;
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('delivery_code', 30)->nullable()->unique()->after('delivery_date')->comment('Mã vận đơn GH-yymmdd-XXXX, sinh khi bấm Xuất kho');
            $table->timestamp('shipped_at')->nullable()->after('delivery_code')->comment('Thời điểm xuất kho');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at')->comment('Thời điểm giao thành công');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sales_orders', 'delivery_code')) {
            return;
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropUnique(['delivery_code']);
            $table->dropColumn(['delivery_code', 'shipped_at', 'delivered_at']);
        });
    }
};
