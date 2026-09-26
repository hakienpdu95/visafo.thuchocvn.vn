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
            if (!Schema::hasColumn('print_logs', 'batch_code')) {
                $table->string('batch_code', 100)->nullable()->after('status_changed_at')->comment('Mã lô tự sinh LOT-[NSX]-[HSD] tại thời điểm in');
            }
            if (!Schema::hasColumn('print_logs', 'vendor_id')) {
                $table->ulid('vendor_id')->nullable()->after('batch_code')->comment('Nhà cung cấp chọn khi in');
            }
            if (!Schema::hasColumn('print_logs', 'product_batch_id')) {
                $table->ulid('product_batch_id')->nullable()->after('vendor_id')->comment('Lô nhập kho chọn khi in');
            }
            if (!Schema::hasColumn('print_logs', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->after('product_batch_id')->comment('Người tạo bản ghi — dùng cho quyền Xem dữ liệu tự tạo');
            }
            if (!Schema::hasIndex('print_logs', 'print_logs_vendor_id_index')) {
                $table->index('vendor_id');
            }
            if (!Schema::hasIndex('print_logs', 'print_logs_product_batch_id_index')) {
                $table->index('product_batch_id');
            }
            if (!Schema::hasIndex('print_logs', 'print_logs_batch_code_index')) {
                $table->index('batch_code');
            }
            if (!Schema::hasIndex('print_logs', 'print_logs_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (Schema::hasColumn('print_logs', 'label_template_id')) $table->dropForeign(['label_template_id']);
            if (Schema::hasColumn('print_logs', 'status_changed_by')) $table->dropForeign(['status_changed_by']);
            if (Schema::hasColumn('print_logs', 'created_by')) $table->dropForeign(['created_by']);
            $cols = array_filter(['label_template_id', 'trace_code', 'print_session_id', 'status', 'status_reason', 'status_changed_by', 'status_changed_at', 'batch_code', 'vendor_id', 'product_batch_id', 'created_by'], fn($c) => Schema::hasColumn('print_logs', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};