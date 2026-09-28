<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_compliance_requirements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('kind', 20)->comment('document | contract');
            $table->foreignUlid('document_master_type_id')->nullable()->constrained('document_master_types')->restrictOnDelete();
            $table->foreignUlid('contract_type_id')->nullable()->constrained('contract_types')->restrictOnDelete();
            $table->string('source_group', 10)->nullable()->comment('NULL = mọi NCC | n1 | n2 | n3 | n4');
            $table->string('group_key', 60)->nullable()->comment('Các dòng cùng group_key: chỉ cần thỏa một');
            $table->string('label', 255);
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedSmallInteger('warning_days')->default(30);
            $table->string('legal_basis', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('source_group', 'idx_vcr_source_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_compliance_requirements');
    }
};
