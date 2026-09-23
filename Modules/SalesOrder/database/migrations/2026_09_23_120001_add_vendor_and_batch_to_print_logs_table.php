<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('print_logs', 'vendor_id')) {
                $table->ulid('vendor_id')->nullable()->after('supplier_name')->comment('Nhà cung cấp chọn khi in');
                $table->ulid('product_batch_id')->nullable()->after('vendor_id')->comment('Lô nhập kho chọn khi in');
                $table->index('vendor_id');
                $table->index('product_batch_id');
                $table->index('batch_code');
                $table->index('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (Schema::hasColumn('print_logs', 'vendor_id')) {
                $table->dropIndex(['vendor_id']);
                $table->dropIndex(['product_batch_id']);
                $table->dropIndex(['batch_code']);
                $table->dropIndex(['created_at']);
                $table->dropColumn(['vendor_id', 'product_batch_id']);
            }
        });
    }
};
