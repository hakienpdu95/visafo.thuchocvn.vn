<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_package_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_package_id')->constrained('sales_packages')->cascadeOnDelete();
            $table->foreignUlid('compliance_document_id')->constrained('compliance_documents')->restrictOnDelete()->comment('Hard-link tài liệu tại thời điểm chốt gói — không đổi theo dữ liệu sống');
            $table->string('document_group', 30)->comment('Snapshot document_master_types.document_group để phân thư mục khi export');
            $table->boolean('is_valid')->default(true)->comment('Tài liệu còn hiệu lực (active, chưa hết hạn) tại thời điểm đưa vào gói');
            $table->timestamps();

            $table->unique(['sales_package_id', 'compliance_document_id'], 'uq_sales_package_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_package_items');
    }
};
