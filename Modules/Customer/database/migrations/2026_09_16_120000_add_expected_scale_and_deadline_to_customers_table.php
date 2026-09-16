<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('expected_scale')->nullable()->after('meal_model')
                ->comment('Quy mô dự kiến — số suất ăn/ngày');
            $table->dateTime('expected_deadline')->nullable()->after('expected_scale')
                ->comment('Hạn nộp hồ sơ/báo giá dự kiến');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['expected_scale', 'expected_deadline']);
        });
    }
};
