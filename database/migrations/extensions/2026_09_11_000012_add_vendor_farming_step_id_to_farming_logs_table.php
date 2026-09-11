<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('farming_logs', 'vendor_farming_step_id')) {
            return;
        }

        Schema::table('farming_logs', function (Blueprint $table) {
            $table->foreignUlid('vendor_farming_step_id')->nullable()->after('agri_pesticide_id')
                ->constrained('vendor_farming_steps')->nullOnDelete()
                ->comment('Vỏ mềm — bước canh tác tự định nghĩa (activity_type=cultivation|other), dùng để hiển thị đúng tên trên Timeline');
        });
    }

    public function down(): void
    {
        Schema::table('farming_logs', function (Blueprint $table) {
            if (Schema::hasColumn('farming_logs', 'vendor_farming_step_id')) {
                $table->dropForeign(['vendor_farming_step_id']);
                $table->dropColumn('vendor_farming_step_id');
            }
        });
    }
};
