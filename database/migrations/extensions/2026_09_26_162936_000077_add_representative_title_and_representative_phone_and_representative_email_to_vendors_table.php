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
            if (!Schema::hasColumn('vendors', 'source_group')) {
                $table->string('source_group', 10)->nullable()->after('ward_code')->comment('n1 = Tự sản xuất | n2 = Thu gom | n3 = Chợ đầu mối / siêu thị | n4 = Doanh nghiệp/ thương mại');
            }
            if (!Schema::hasIndex('vendors', 'idx_vendors_source_group')) {
                $table->index('source_group', 'idx_vendors_source_group');
            }
            if (!Schema::hasColumn('vendors', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->after('source_group')->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'province_code')) $table->dropForeign(['province_code']);
            if (Schema::hasColumn('vendors', 'ward_code')) $table->dropForeign(['ward_code']);
            if (Schema::hasColumn('vendors', 'created_by')) $table->dropForeign(['created_by']);
            $cols = array_filter(['representative_title', 'representative_phone', 'representative_email', 'contact_person_name', 'contact_person_title', 'contact_person_phone', 'contact_person_email', 'province_code', 'ward_code', 'source_group', 'created_by'], fn($c) => Schema::hasColumn('vendors', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};