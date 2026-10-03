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
        Schema::table('products', function (Blueprint $table) {
            $cols = array_filter(['category_type'], fn($c) => Schema::hasColumn('products', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // TODO: $table->string('category_type')->...; // add lại 'category_type'
        });
    }
};