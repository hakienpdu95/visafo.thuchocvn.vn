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
            if (!Schema::hasColumn('print_logs', 'print_session_id')) {
                $table->string('print_session_id', 26)->nullable()->index()->after('trace_code')->comment('Mã gom nhóm các tem của cùng một lần in');
            }
            if (!Schema::hasColumn('print_logs', 'status')) {
                $table->string('status', 20)->default('active')->index()->after('print_session_id')->comment('active | recalled | error — QC đánh dấu tem thu hồi/lỗi');
            }
            if (!Schema::hasColumn('print_logs', 'status_reason')) {
                $table->string('status_reason', 255)->nullable()->after('status')->comment('Lý do thu hồi/lỗi (hiển thị trên trang truy xuất công khai)');
            }
            if (!Schema::hasColumn('print_logs', 'status_changed_by')) {
                $table->foreignUlid('status_changed_by')->nullable()->constrained('users')->nullOnDelete()->after('status_reason')->comment('Người đổi trạng thái gần nhất');
            }
            if (!Schema::hasColumn('print_logs', 'status_changed_at')) {
                $table->timestamp('status_changed_at')->nullable()->after('status_changed_by')->comment('Thời điểm đổi trạng thái gần nhất');
            }
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (Schema::hasColumn('print_logs', 'label_template_id')) $table->dropForeign(['label_template_id']);
            if (Schema::hasColumn('print_logs', 'status_changed_by')) $table->dropForeign(['status_changed_by']);
            $cols = array_filter(['label_template_id', 'trace_code', 'print_session_id', 'status', 'status_reason', 'status_changed_by', 'status_changed_at'], fn($c) => Schema::hasColumn('print_logs', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};