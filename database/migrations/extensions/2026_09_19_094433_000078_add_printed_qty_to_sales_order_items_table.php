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
        Schema::table('sales_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_order_items', 'printed_qty')) {
                $table->decimal('printed_qty', 14, 3)->default(0)->comment('Tổng khối lượng (kg) đã in tem — cộng dồn theo từng lần in');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $cols = array_filter(['printed_qty'], fn($c) => Schema::hasColumn('sales_order_items', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};