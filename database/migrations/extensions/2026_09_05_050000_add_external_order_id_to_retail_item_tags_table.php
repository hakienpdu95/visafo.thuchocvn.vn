<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('retail_item_tags', 'external_order_id')) {
            Schema::table('retail_item_tags', function (Blueprint $table) {
                $table->foreignUlid('external_order_id')->nullable()->after('sold_at')
                    ->constrained('external_orders')->nullOnDelete()
                    ->comment('Đơn hàng POS đã bán tem này — dùng để truy vết ngược khách hàng khi thu hồi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('retail_item_tags', 'external_order_id')) {
            Schema::table('retail_item_tags', function (Blueprint $table) {
                $table->dropConstrainedForeignId('external_order_id');
            });
        }
    }
};
