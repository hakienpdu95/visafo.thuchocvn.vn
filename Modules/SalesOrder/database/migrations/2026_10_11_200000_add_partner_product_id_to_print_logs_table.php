<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Hàng hóa NCC chọn khi in — nullable: tem cũ (null) vẫn truy xuất theo vendor_id + product_id như trước. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('print_logs', 'partner_product_id')) {
            return;
        }

        Schema::table('print_logs', function (Blueprint $table) {
            $table->ulid('partner_product_id')->nullable()->after('vendor_id')->comment('Hàng hóa NCC chọn khi in (null = suy ra từ vendor_id + product_id)');
            $table->index('partner_product_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('print_logs', 'partner_product_id')) {
            return;
        }

        Schema::table('print_logs', function (Blueprint $table) {
            $table->dropIndex(['partner_product_id']);
            $table->dropColumn('partner_product_id');
        });
    }
};
