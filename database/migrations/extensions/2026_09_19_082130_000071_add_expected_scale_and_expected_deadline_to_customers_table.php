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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'expected_scale')) {
                $table->unsignedInteger('expected_scale')->nullable()->comment('Quy mô dự kiến — số suất ăn/ngày');
            }
            if (!Schema::hasColumn('customers', 'expected_deadline')) {
                $table->dateTime('expected_deadline')->nullable()->after('expected_scale')->comment('Hạn nộp hồ sơ/báo giá dự kiến');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $cols = array_filter(['expected_scale', 'expected_deadline'], fn($c) => Schema::hasColumn('customers', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};