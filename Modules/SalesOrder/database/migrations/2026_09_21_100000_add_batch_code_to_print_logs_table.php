<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('print_logs', 'batch_code')) {
                $table->string('batch_code', 100)->nullable()->after('supplier_name')
                    ->comment('Mã lô tự sinh LOT-[NSX]-[HSD] tại thời điểm in');
            }
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (Schema::hasColumn('print_logs', 'batch_code')) {
                $table->dropColumn('batch_code');
            }
        });
    }
};
