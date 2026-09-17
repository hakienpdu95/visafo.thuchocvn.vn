<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_packages', function (Blueprint $table) {
            $table->dateTime('expected_deadline')->nullable()->after('name')
                ->comment('Hạn nộp hồ sơ/báo giá dự kiến — auto-fill từ Customer.expected_deadline, Sale có thể sửa lại');
        });
    }

    public function down(): void
    {
        Schema::table('sales_packages', function (Blueprint $table) {
            $table->dropColumn('expected_deadline');
        });
    }
};
