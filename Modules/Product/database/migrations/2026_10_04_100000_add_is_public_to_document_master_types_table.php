<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Hồ sơ doanh nghiệp được công khai mặc định trên tab "Thương hiệu" của trang truy xuất. */
    private const PUBLIC_CODES = ['internal_business_registration', 'facility_attp', 'internal_haccp'];

    public function up(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('is_transactional');
        });

        DB::table('document_master_types')->whereIn('code', self::PUBLIC_CODES)->update(['is_public' => true]);
    }

    public function down(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
