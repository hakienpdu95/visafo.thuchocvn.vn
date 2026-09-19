<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('print_logs', 'status')) {
                $table->string('status', 20)->default('active')->index('idx_print_logs_status')->after('print_session_id')
                    ->comment('active | recalled | error — QC đánh dấu tem thu hồi/lỗi');
            }
            if (! Schema::hasColumn('print_logs', 'status_reason')) {
                $table->string('status_reason', 255)->nullable()->after('status')
                    ->comment('Lý do thu hồi/lỗi (hiển thị trên trang truy xuất công khai)');
            }
            if (! Schema::hasColumn('print_logs', 'status_changed_by')) {
                $table->foreignUlid('status_changed_by')->nullable()->after('status_reason')
                    ->constrained('users')->nullOnDelete()->comment('Người đổi trạng thái gần nhất');
            }
            if (! Schema::hasColumn('print_logs', 'status_changed_at')) {
                $table->timestamp('status_changed_at')->nullable()->after('status_changed_by')
                    ->comment('Thời điểm đổi trạng thái gần nhất');
            }
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (Schema::hasColumn('print_logs', 'status_changed_by')) {
                $table->dropConstrainedForeignId('status_changed_by');
            }
            foreach (['status_changed_at', 'status_reason'] as $col) {
                if (Schema::hasColumn('print_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('print_logs', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });
    }
};
