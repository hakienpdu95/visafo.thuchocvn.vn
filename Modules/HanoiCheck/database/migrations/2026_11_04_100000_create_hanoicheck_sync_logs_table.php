<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hanoicheck_sync_logs')) {
            return;
        }

        Schema::create('hanoicheck_sync_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('entity_type', 40);
            $table->string('local_id', 40);
            $table->string('status', 20)->index();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->string('environment', 20);
            $table->string('http_method', 10)->nullable();
            $table->string('endpoint', 500)->nullable();
            $table->longText('request_payload')->nullable();
            $table->char('payload_hash', 64)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->string('request_nonce', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['entity_type', 'local_id', 'endpoint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hanoicheck_sync_logs');
    }
};
