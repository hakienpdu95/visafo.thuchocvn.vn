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
        if (Schema::hasTable('compliance_documents')) {
            return;
        }

        Schema::create('compliance_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('document_master_type_id')->constrained('document_master_types')->restrictOnDelete();
            $table->string('documentable_type', 50)->index()->comment('Morph alias — vendor | product | partner_product');
            $table->ulid('documentable_id')->index();
            $table->string('document_number', 150)->nullable()->comment('Số tiếp nhận / số chứng nhận');
            $table->string('classification_grade', 10)->nullable()->comment('A | B | C | D — chỉ có ý nghĩa khi documentable_type=product');
            $table->date('issue_date')->nullable()->comment('Ngày cấp');
            $table->date('expiration_date')->nullable()->index()->comment('Ngày hết hạn — phục vụ cronjob cảnh báo');
            $table->string('issued_by', 255)->nullable()->comment('Nơi cấp');
            $table->string('status', 20)->default('pending')->index()->comment('pending | active | expired | superseded');
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['documentable_type', 'documentable_id'], 'idx_compliance_documents_documentable');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_documents');
    }
};