<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('traceability_scan_logs')) {
            return;
        }

        Schema::create('traceability_scan_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('retail_item_tag_id')->nullable()->constrained('retail_item_tags')->nullOnDelete()->comment('Null nếu quét mã không tồn tại (nghi giả)');
            $table->string('scanned_code', 500)->comment('Chuỗi thực tế được quét — dùng để đối chiếu khi tag null');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['retail_item_tag_id', 'created_at'], 'idx_scan_log_tag_time');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('traceability_scan_logs');
    }
};