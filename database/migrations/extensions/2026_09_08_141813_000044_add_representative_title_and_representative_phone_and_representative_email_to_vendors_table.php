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
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'representative_title')) {
                $table->string('representative_title', 100)->nullable()->after('representative_name')->comment('Chức danh người đại diện — VD: Giám đốc, Chủ tịch HĐQT');
            }
            if (!Schema::hasColumn('vendors', 'representative_phone')) {
                $table->string('representative_phone', 20)->nullable()->after('representative_title')->comment('Điện thoại cá nhân người đại diện');
            }
            if (!Schema::hasColumn('vendors', 'representative_email')) {
                $table->string('representative_email', 100)->nullable()->after('representative_phone')->comment('Email cá nhân người đại diện');
            }
            if (!Schema::hasColumn('vendors', 'contact_person_name')) {
                $table->string('contact_person_name', 100)->nullable()->after('representative_email')->comment('Đầu mối liên hệ về công việc — nhân sự làm việc trực tiếp hàng ngày');
            }
            if (!Schema::hasColumn('vendors', 'contact_person_title')) {
                $table->string('contact_person_title', 100)->nullable()->after('contact_person_name')->comment('Chức vụ đầu mối liên hệ — VD: Sales, Kế toán trưởng');
            }
            if (!Schema::hasColumn('vendors', 'contact_person_phone')) {
                $table->string('contact_person_phone', 20)->nullable()->after('contact_person_title')->comment('Điện thoại đầu mối liên hệ');
            }
            if (!Schema::hasColumn('vendors', 'contact_person_email')) {
                $table->string('contact_person_email', 100)->nullable()->after('contact_person_phone')->comment('Email đầu mối liên hệ');
            }
            if (!Schema::hasColumn('vendors', 'province_code')) {
                $table->char('province_code', 2)->nullable()->after('contact_person_email')->comment('Tỉnh/thành phố trụ sở — FK tới provinces.province_code');
                $table->foreign('province_code')->references('province_code')->on('provinces')->nullOnDelete();
            }
            if (!Schema::hasColumn('vendors', 'ward_code')) {
                $table->char('ward_code', 5)->nullable()->after('province_code')->comment('Phường/xã trụ sở — FK tới wards.ward_code');
                $table->foreign('ward_code')->references('ward_code')->on('wards')->nullOnDelete();
            }
            if (!Schema::hasIndex('vendors', 'idx_vendors_address')) {
                $table->index(['province_code', 'ward_code'], 'idx_vendors_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'province_code')) $table->dropForeign(['province_code']);
            if (Schema::hasColumn('vendors', 'ward_code')) $table->dropForeign(['ward_code']);
            $cols = array_filter(['representative_title', 'representative_phone', 'representative_email', 'contact_person_name', 'contact_person_title', 'contact_person_phone', 'contact_person_email', 'province_code', 'ward_code'], fn($c) => Schema::hasColumn('vendors', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};