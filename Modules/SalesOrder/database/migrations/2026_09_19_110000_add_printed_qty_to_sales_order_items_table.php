<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_order_items', 'printed_qty')) {
                $table->decimal('printed_qty', 14, 3)->default(0)->after('actual_qty')
                    ->comment('Tổng khối lượng (kg) đã in tem — cộng dồn theo từng lần in');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_order_items', 'printed_qty')) {
                $table->dropColumn('printed_qty');
            }
        });
    }
};
