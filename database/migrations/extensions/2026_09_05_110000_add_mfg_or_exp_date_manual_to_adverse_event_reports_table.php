<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('adverse_event_reports', 'mfg_or_exp_date_manual')) {
            Schema::table('adverse_event_reports', function (Blueprint $table) {
                $table->string('mfg_or_exp_date_manual', 150)->nullable()->after('lot_number_manual');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('adverse_event_reports', 'mfg_or_exp_date_manual')) {
            Schema::table('adverse_event_reports', function (Blueprint $table) {
                $table->dropColumn('mfg_or_exp_date_manual');
            });
        }
    }
};
