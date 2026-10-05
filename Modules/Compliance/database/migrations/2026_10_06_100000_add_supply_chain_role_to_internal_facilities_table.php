<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('internal_facilities', 'supply_chain_role')) {
            return;
        }

        Schema::table('internal_facilities', function (Blueprint $table) {
            $table->longText('supply_chain_role')->nullable()->after('address')
                ->comment('Vai trò trong chuỗi cung ứng (HTML từ Jodit, đã làm sạch) — hiển thị ở khối "Đơn vị cung ứng" trang truy xuất');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('internal_facilities', 'supply_chain_role')) {
            Schema::table('internal_facilities', fn (Blueprint $table) => $table->dropColumn('supply_chain_role'));
        }
    }
};
