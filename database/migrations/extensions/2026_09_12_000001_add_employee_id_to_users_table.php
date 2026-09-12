<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('users', 'employee_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUlid('employee_id')->nullable()->after('vendor_id')->constrained('employees')->nullOnDelete()->comment('Nhân viên liên kết — bắt buộc với mọi role nội bộ trừ system_admin và farmer');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'employee_id')) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            }
        });
    }
};
