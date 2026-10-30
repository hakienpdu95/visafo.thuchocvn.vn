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
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
            if (!Schema::hasColumn('employees', 'gender')) {
                $table->string('gender', 10)->nullable()->after('created_by')->comment('nam | nu | khac');
            }
            if (!Schema::hasColumn('employees', 'dob')) {
                $table->date('dob')->nullable()->after('gender')->comment('Ngày sinh');
            }
            if (!Schema::hasColumn('employees', 'start_date')) {
                $table->date('start_date')->nullable()->after('dob')->comment('Ngày bắt đầu làm việc');
            }
            if (!Schema::hasColumn('employees', 'address')) {
                $table->string('address', 500)->nullable()->after('start_date')->comment('Địa chỉ liên hệ');
            }
            if (!Schema::hasColumn('employees', 'id_card_number')) {
                $table->string('id_card_number', 20)->nullable()->index()->after('address')->comment('Số CCCD (12 số)');
            }
            if (!Schema::hasColumn('employees', 'id_card_issue_date')) {
                $table->date('id_card_issue_date')->nullable()->after('id_card_number')->comment('Ngày cấp CCCD');
            }
            if (!Schema::hasColumn('employees', 'id_card_issue_place')) {
                $table->string('id_card_issue_place', 255)->nullable()->after('id_card_issue_date')->comment('Nơi cấp CCCD');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'created_by')) $table->dropForeign(['created_by']);
            $cols = array_filter(['created_by', 'gender', 'dob', 'start_date', 'address', 'id_card_number', 'id_card_issue_date', 'id_card_issue_place'], fn($c) => Schema::hasColumn('employees', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};