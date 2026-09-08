<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('compliance_warnings')) {
            return;
        }

        Schema::create('compliance_warnings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete()->comment('Tổ chức sở hữu');
            $table->string('warnable_type', 50)->index()->comment('Morph alias — product_compliance | vendor_certificate | batch');
            $table->ulid('warnable_id');
            $table->index(['warnable_type', 'warnable_id'], 'idx_warnings_warnable');
            $table->string('category', 50)->index()->comment('product_compliance_expiry | vendor_certificate_expiry | batch_near_expiry');
            $table->string('title', 255);
            $table->text('message');
            $table->date('due_date')->index()->comment('Ngày hết hạn/cần gia hạn thực tế');
            $table->string('severity', 20)->default('warning')->index()->comment('warning | critical');
            $table->string('status', 20)->default('pending')->index()->comment('pending | acknowledged | resolved');
            $table->foreignUlid('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['warnable_type', 'warnable_id', 'category'], 'uq_warning_warnable_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_warnings');
    }
};
