<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * In lại tem dùng lại đúng mã TXNG cũ (không tạo bản ghi mới) → theo dõi số lần in và phiên in gần nhất.
 * last_print_session_id: phiên in mới nhất chứa tem này (trang in render theo print_session_id HOẶC cột này).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('print_logs', 'print_count')) {
                $table->unsignedInteger('print_count')->default(1)->after('print_session_id')->comment('Số lần in tem này');
                $table->timestamp('last_printed_at')->nullable()->after('print_count')->comment('Lần in gần nhất');
                $table->string('last_print_session_id', 26)->nullable()->after('last_printed_at')->comment('Phiên in gần nhất chứa tem này');
                $table->index('last_print_session_id');
                $table->index(['order_item_id', 'status']);
            }
        });

        DB::table('print_logs')->whereNull('last_print_session_id')->update([
            'last_print_session_id' => DB::raw('print_session_id'),
            'last_printed_at'       => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (Schema::hasColumn('print_logs', 'print_count')) {
                $table->dropIndex(['last_print_session_id']);
                $table->dropIndex(['order_item_id', 'status']);
                $table->dropColumn(['print_count', 'last_printed_at', 'last_print_session_id']);
            }
        });
    }
};
