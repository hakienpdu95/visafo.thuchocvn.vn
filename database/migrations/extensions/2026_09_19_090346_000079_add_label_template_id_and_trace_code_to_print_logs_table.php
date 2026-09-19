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
        Schema::table('print_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('print_logs', 'label_template_id')) {
                $table->foreignUlid('label_template_id')->nullable()->constrained('label_templates')->nullOnDelete()->comment('Mẫu tem đã chọn khi in — NULL = theo mẫu gán cho sản phẩm, hoặc mẫu mặc định');
            }
            if (!Schema::hasColumn('print_logs', 'trace_code')) {
                $table->string('trace_code', 16)->nullable()->unique()->after('label_template_id')->comment('Mã truy xuất công khai (ngẫu nhiên) dùng trong QR — không lộ ID tuần tự');
            }
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (Schema::hasColumn('print_logs', 'label_template_id')) $table->dropForeign(['label_template_id']);
            $cols = array_filter(['label_template_id', 'trace_code'], fn($c) => Schema::hasColumn('print_logs', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};