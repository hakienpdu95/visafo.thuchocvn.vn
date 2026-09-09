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
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->ulid('organization_id')->nullable()->index()->comment('Tenant context — NULL khi CLI/system job');
            $table->ulid('user_id')->nullable()->index()->comment('Người thực hiện thay đổi');
            $table->string('model_type', 255)->comment('FQCN của model bị thay đổi');
            $table->ulid('model_id')->nullable();
            $table->string('action', 32)->comment('create | update | delete');
            $table->text('old_values')->nullable()->comment('Giá trị trước khi đổi (JSON-encoded, không dùng cột JSON)');
            $table->text('new_values')->nullable()->comment('Giá trị sau khi đổi (JSON-encoded, không dùng cột JSON)');
            $table->string('ip_address', 45)->nullable()->comment('IPv4 hoặc IPv6');
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['model_type', 'model_id'], 'idx_audit_model');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};