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
        if (Schema::hasTable('label_print_events')) {
            return;
        }

        Schema::create('label_print_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->char('print_log_id', 26);
            $table->char('order_item_id', 26);
            $table->string('print_session_id', 26);
            $table->char('user_id', 26)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_reprint')->default(false);
            $table->timestamp('printed_at')->useCurrent();
            

            // Indexes
            $table->index('print_log_id');
            $table->index(['order_item_id', 'print_session_id']);
            $table->index('printed_at');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('label_print_events');
    }
};