<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SRS01-FR-ORG-005 (GAP_ANALYSIS_v1.0.md §3.3 ORG-02): version hoá config tổ chức
 * (profile + policy) — mỗi lần Organization Admin publish, tạo 1 version mới,
 * bản trước chuyển 'superseded'. Approved/Active immutable — sửa phải tạo version mới.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
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

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['organization_id', 'version'], 'uq_org_version');
            $table->index(['organization_id', 'status'], 'idx_org_versions_status');
        });

        DB::statement("ALTER TABLE organization_versions ADD CONSTRAINT chk_org_version_status CHECK (status IN ('draft','active','superseded'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_versions');
    }
};
