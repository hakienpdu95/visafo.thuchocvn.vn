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
        Schema::table('sales_package_items', function (Blueprint $table) {
            $cols = array_filter(['order_column'], fn($c) => Schema::hasColumn('sales_package_items', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }

    public function down(): void
    {
        Schema::table('sales_package_items', function (Blueprint $table) {
            // TODO: $table->unsignedInteger('order_column')->...; // add lại 'order_column'
        });
    }
};