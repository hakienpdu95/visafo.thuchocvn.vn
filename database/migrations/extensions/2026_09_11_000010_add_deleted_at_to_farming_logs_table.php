<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('farming_logs', 'deleted_at')) {
            return;
        }

        Schema::table('farming_logs', function (Blueprint $table) {
            $table->softDeletes()->comment('Thời gian xóa mềm — QC/Admin xóa nhật ký nhưng vẫn giữ vết trong DB');
        });
    }

    public function down(): void
    {
        Schema::table('farming_logs', function (Blueprint $table) {
            if (Schema::hasColumn('farming_logs', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
};
