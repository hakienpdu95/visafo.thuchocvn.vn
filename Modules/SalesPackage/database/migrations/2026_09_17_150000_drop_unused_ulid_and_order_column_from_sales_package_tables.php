<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dọn 2 cột `ulid` và `order_column` bị lệch do migration generated/extensions
 * (database/migrations/generated|extensions) tạo bảng trước migration thật của module,
 * để lại cột `ulid` NOT NULL không có default -> insert lỗi SQLSTATE[HY000] 1364.
 * Model dùng HasUlids trên cột `id` sẵn rồi nên 2 cột này không được dùng ở đâu cả.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_packages', function (Blueprint $table) {
            if (Schema::hasColumn('sales_packages', 'ulid')) {
                $table->dropColumn('ulid');
            }
            if (Schema::hasColumn('sales_packages', 'order_column')) {
                $table->dropColumn('order_column');
            }
        });

        Schema::table('sales_package_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_package_items', 'ulid')) {
                $table->dropColumn('ulid');
            }
            if (Schema::hasColumn('sales_package_items', 'order_column')) {
                $table->dropColumn('order_column');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_packages', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->after('id');
            $table->unsignedInteger('order_column')->nullable()->after('id');
        });

        Schema::table('sales_package_items', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->after('id');
            $table->unsignedInteger('order_column')->nullable()->after('id');
        });
    }
};
