<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('retail_item_tags', 'outbound_order_id')) {
            Schema::table('retail_item_tags', function (Blueprint $table) {
                $table->foreignUlid('outbound_order_id')->nullable()->after('external_order_id')
                    ->constrained('outbound_orders')->nullOnDelete()
                    ->comment('Đơn xuất buôn (B2B) đã nhận tem này — dùng để truy vết ngược đại lý khi có hàng phá giá/rò rỉ');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('retail_item_tags', 'outbound_order_id')) {
            Schema::table('retail_item_tags', function (Blueprint $table) {
                $table->dropConstrainedForeignId('outbound_order_id');
            });
        }
    }
};
