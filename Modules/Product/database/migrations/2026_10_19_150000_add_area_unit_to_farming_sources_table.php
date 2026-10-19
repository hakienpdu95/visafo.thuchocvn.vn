<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('farming_sources', 'area_unit')) {
            return;
        }

        Schema::table('farming_sources', function (Blueprint $table) {
            $table->decimal('area_hectare', 12, 4)->nullable()->comment('Diện tích canh tác quy đổi (ha) — tính từ area_value × area_unit')->change();
            $table->decimal('area_value', 12, 2)->nullable()->after('area_hectare')->comment('Diện tích theo đơn vị người dùng nhập (VD: 2)');
            $table->string('area_unit', 20)->nullable()->after('area_value')->comment('sao_bac_bo | sao_trung_bo | m2 | ha');
        });

        DB::table('farming_sources')
            ->whereNotNull('area_hectare')
            ->update(['area_value' => DB::raw('area_hectare'), 'area_unit' => 'ha']);
    }

    public function down(): void
    {
        if (!Schema::hasColumn('farming_sources', 'area_unit')) {
            return;
        }

        Schema::table('farming_sources', function (Blueprint $table) {
            $table->dropColumn(['area_value', 'area_unit']);
            $table->decimal('area_hectare', 8, 2)->nullable()->comment('Diện tích canh tác (ha)')->change();
        });
    }
};
