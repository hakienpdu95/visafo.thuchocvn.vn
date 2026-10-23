<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sales_orders', 'customer_id')) {
            return;
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignUlid('customer_id')->nullable()->after('misa_ref_id')
                ->constrained('customers')->nullOnDelete()
                ->comment('Khách hàng liên kết (đơn nhập tay) — customer_name/delivery_address vẫn lưu snapshot');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sales_orders', 'customer_id')) {
            return;
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
