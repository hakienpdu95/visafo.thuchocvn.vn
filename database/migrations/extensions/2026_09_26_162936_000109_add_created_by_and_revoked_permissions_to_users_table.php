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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
            if (!Schema::hasIndex('users', 'users_created_by_index')) {
                $table->index('created_by', 'users_created_by_index');
            }
            if (!Schema::hasColumn('users', 'revoked_permissions')) {
                $table->json('revoked_permissions')->nullable()->after('created_by')->comment('Quyền của Role gốc bị gỡ riêng cho user này');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter(['created_by', 'revoked_permissions'], fn($c) => Schema::hasColumn('users', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};