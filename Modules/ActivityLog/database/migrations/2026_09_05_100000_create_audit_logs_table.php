<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id')->nullable();
            $table->ulid('user_id')->nullable();
            $table->string('model_type', 255);
            $table->ulid('model_id')->nullable();
            $table->string('action', 32)->comment('create|update|delete');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['model_type', 'model_id'], 'idx_audit_model');
            $table->index(['organization_id', 'created_at'], 'idx_audit_org_created');
            $table->index('user_id', 'idx_audit_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
