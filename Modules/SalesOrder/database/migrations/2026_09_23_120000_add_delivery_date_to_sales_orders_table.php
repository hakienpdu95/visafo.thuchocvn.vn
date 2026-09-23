<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_orders', 'delivery_date')) {
                $table->date('delivery_date')->nullable()->after('delivery_address')->comment('Ngày giao hàng');
                $table->index('delivery_date');
                $table->index('created_at');
                $table->index('customer_name');
            }
        });

        DB::table('sales_orders')->whereNull('delivery_date')->orderBy('id')->get(['id', 'created_at'])
            ->each(fn ($o) => DB::table('sales_orders')->where('id', $o->id)
                ->update(['delivery_date' => Carbon::parse($o->created_at)->addDay()->toDateString()]));
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'delivery_date')) {
                $table->dropIndex(['delivery_date']);
                $table->dropIndex(['created_at']);
                $table->dropIndex(['customer_name']);
                $table->dropColumn('delivery_date');
            }
        });
    }
};
