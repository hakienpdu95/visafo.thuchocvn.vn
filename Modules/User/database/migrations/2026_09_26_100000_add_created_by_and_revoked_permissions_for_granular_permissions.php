<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'products',
        'partner_products',
        'vendors',
        'goods_receipts',
        'product_batches',
        'food_inspection_step1_logs',
        'food_inspection_step2_logs',
        'food_inspection_step3_logs',
        'food_sample_logs',
        'menus',
        'sales_orders',
        'label_templates',
        'print_logs',
        'customers',
        'sales_packages',
        'contracts',
        'compliance_documents',
        'internal_facilities',
        'farming_batches',
        'farming_sources',
        'vendor_farming_steps',
        'employees',
        'departments',
        'users',
    ];

    private const BACKFILL_FROM = [
        'print_logs'                 => 'printed_by',
        'food_inspection_step1_logs' => 'inspected_by',
        'food_inspection_step2_logs' => 'inspected_by',
        'food_inspection_step3_logs' => 'inspected_by',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'created_by')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $column = $t->foreignUlid('created_by')->nullable()->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');

                if ($table === 'users') {
                    $t->index('created_by');
                } else {
                    $column->constrained('users')->nullOnDelete();
                }
            });
        }

        foreach (self::BACKFILL_FROM as $table => $source) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $source)) {
                DB::table($table)
                    ->whereNull('created_by')
                    ->whereNotNull($source)
                    ->whereIn($source, DB::table('users')->select('id'))
                    ->update(['created_by' => DB::raw($source)]);
            }
        }

        if (! Schema::hasColumn('users', 'revoked_permissions')) {
            Schema::table('users', function (Blueprint $t) {
                $t->json('revoked_permissions')->nullable()->comment('Quyền của Role gốc bị gỡ riêng cho user này');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'revoked_permissions')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('revoked_permissions'));
        }

        foreach (self::TABLES as $table) {
            if ($table === 'food_sample_logs' || $table === 'sales_packages') {
                continue;
            }

            if (Schema::hasTable($table) && Schema::hasColumn($table, 'created_by')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    if ($table === 'users') {
                        $t->dropIndex(['created_by']);
                    } else {
                        $t->dropForeign(['created_by']);
                    }
                    $t->dropColumn('created_by');
                });
            }
        }
    }
};
