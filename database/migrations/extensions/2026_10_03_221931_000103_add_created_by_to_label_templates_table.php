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
        Schema::table('label_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('label_templates', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('label_templates', function (Blueprint $table) {
            if (Schema::hasColumn('label_templates', 'created_by')) $table->dropForeign(['created_by']);
            $cols = array_filter(['created_by'], fn($c) => Schema::hasColumn('label_templates', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};