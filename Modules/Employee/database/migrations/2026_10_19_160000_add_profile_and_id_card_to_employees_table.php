<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'id_card_number')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->after('full_name')->comment('nam | nu | khac');
            $table->date('dob')->nullable()->after('gender')->comment('Ngày sinh');
            $table->date('start_date')->nullable()->after('job_title')->comment('Ngày bắt đầu làm việc');
            $table->string('address', 500)->nullable()->after('facebook_url')->comment('Địa chỉ liên hệ');
            $table->string('id_card_number', 20)->nullable()->after('address')->index()->comment('Số CCCD (12 số)');
            $table->date('id_card_issue_date')->nullable()->after('id_card_number')->comment('Ngày cấp CCCD');
            $table->string('id_card_issue_place', 255)->nullable()->after('id_card_issue_date')->comment('Nơi cấp CCCD');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('employees', 'id_card_number')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['id_card_number']);
            $table->dropColumn(['gender', 'dob', 'start_date', 'address', 'id_card_number', 'id_card_issue_date', 'id_card_issue_place']);
        });
    }
};
