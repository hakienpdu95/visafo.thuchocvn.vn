<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'transporter_id', 'remote_status', 'school_name', 'sync_status', 'sync_message', 'process_hash', 'snapshot', 'fetched_at', 'synced_at',
    ];

    public function up(): void
    {
        if (Schema::hasColumn('hanoicheck_order_links', 'sync_status')) {
            return;
        }

        Schema::table('hanoicheck_order_links', function (Blueprint $table) {
            $table->char('transporter_id', 26)->nullable()->after('linked_at')->index()->comment('Người giao hàng (hanoicheck_transporters.id)');
            $table->string('remote_status', 30)->nullable()->after('transporter_id')->index()->comment('Trạng thái đơn trên HanoiCheck lần đọc gần nhất');
            $table->string('school_name', 255)->nullable()->after('remote_status');
            $table->string('sync_status', 20)->default('idle')->after('school_name')->index();
            $table->text('sync_message')->nullable()->after('sync_status');
            $table->char('process_hash', 64)->nullable()->after('sync_message')->comment('Vân tay nội dung /process đã gửi — không gửi lại khi không đổi');
            $table->json('snapshot')->nullable()->after('process_hash')->comment('Dòng hàng + phân bổ lô/kho lần đồng bộ gần nhất');
            $table->timestamp('fetched_at')->nullable()->after('snapshot');
            $table->timestamp('synced_at')->nullable()->after('fetched_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('hanoicheck_order_links', 'sync_status')) {
            return;
        }

        Schema::table('hanoicheck_order_links', function (Blueprint $table) {
            $table->dropIndex(['transporter_id']);
            $table->dropIndex(['remote_status']);
            $table->dropIndex(['sync_status']);
            $table->dropColumn(self::COLUMNS);
        });
    }
};
