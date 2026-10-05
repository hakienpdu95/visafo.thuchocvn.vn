<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('label_print_events')) {
            Schema::create('label_print_events', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('print_log_id', 26);
                $table->char('order_item_id', 26);
                $table->string('print_session_id', 26);
                $table->char('user_id', 26)->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->boolean('is_reprint')->default(false);
                $table->timestamp('printed_at');

                $table->index('print_log_id');
                $table->index(['order_item_id', 'print_session_id']);
                $table->index('printed_at');
                $table->foreign('print_log_id')->references('id')->on('print_logs')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (DB::table('label_print_events')->exists()) {
            return;
        }

        $hasReprint = Schema::hasColumn('print_logs', 'last_print_session_id');

        DB::table('print_logs')->whereNull('deleted_at')->orderBy('id')
            ->chunk(500, function ($logs) use ($hasReprint) {
                $rows = [];
                foreach ($logs as $log) {
                    $session = $log->print_session_id ?? Str::lower((string) Str::ulid());
                    $rows[] = [
                        'id'               => Str::lower((string) Str::ulid()),
                        'print_log_id'     => $log->id,
                        'order_item_id'    => $log->order_item_id,
                        'print_session_id' => $session,
                        'user_id'          => $log->printed_by,
                        'quantity'         => 1,
                        'is_reprint'       => false,
                        'printed_at'       => $log->created_at ?? now(),
                    ];

                    if ($hasReprint && $log->last_print_session_id && $log->last_print_session_id !== $session) {
                        $rows[] = [
                            'id'               => Str::lower((string) Str::ulid()),
                            'print_log_id'     => $log->id,
                            'order_item_id'    => $log->order_item_id,
                            'print_session_id' => $log->last_print_session_id,
                            'user_id'          => null,
                            'quantity'         => 1,
                            'is_reprint'       => true,
                            'printed_at'       => $log->last_printed_at ?? $log->created_at ?? now(),
                        ];
                    }
                }

                DB::table('label_print_events')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_print_events');
    }
};
