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
        if (Schema::hasTable('organization_versions')) {
            return;
        }

        Schema::create('organization_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->json('config_json');
            $table->string('checksum', 64)->comment('sha256(config_json) — phát hiện thay đổi ngoài workflow');
            $table->string('status', 20)->default('draft');
            $table->timestamp('effective_at')->nullable();
            $table->text('change_reason')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->unique(['organization_id', 'version'], 'uq_org_version');
            $table->index(['organization_id', 'status'], 'idx_org_versions_status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_versions');
    }
};