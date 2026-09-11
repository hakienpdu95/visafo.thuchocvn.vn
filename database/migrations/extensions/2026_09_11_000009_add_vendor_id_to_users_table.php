<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('users', 'vendor_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUlid('vendor_id')->nullable()->after('id')->constrained('vendors')->nullOnDelete()->comment('Nông hộ liên kết — chỉ có ở tài khoản role farmer');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });
    }
};
