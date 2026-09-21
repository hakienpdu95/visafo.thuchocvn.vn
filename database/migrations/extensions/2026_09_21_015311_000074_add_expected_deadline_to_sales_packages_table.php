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
        Schema::table('sales_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_packages', 'expected_deadline')) {
                $table->dateTime('expected_deadline')->nullable()->comment('Hạn nộp hồ sơ/báo giá dự kiến — auto-fill từ Customer.expected_deadline, Sale có thể sửa lại');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_packages', function (Blueprint $table) {
            $cols = array_filter(['expected_deadline'], fn($c) => Schema::hasColumn('sales_packages', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};