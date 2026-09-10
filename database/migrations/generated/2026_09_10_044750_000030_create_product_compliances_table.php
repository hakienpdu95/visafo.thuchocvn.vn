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
        if (Schema::hasTable('product_compliances')) {
            return;
        }

        Schema::create('product_compliances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('document_type_id')->constrained('document_master_types')->restrictOnDelete();
            $table->string('document_number', 150)->comment('Số tiếp nhận / số chứng nhận / số lưu hành');
            $table->string('classification_grade', 5)->nullable()->comment('A | B | C | D — chỉ dùng cho thiết bị y tế');
            $table->date('issue_date')->nullable()->comment('Ngày cấp');
            $table->date('expiration_date')->nullable()->index()->comment('Ngày hết hạn — phục vụ cronjob cảnh báo');
            $table->string('file_url', 500)->nullable()->comment('File scan bản chính');
            $table->string('pif_file_url', 500)->nullable()->comment('File đính kèm PIF — chỉ bắt buộc với mỹ phẩm');
            $table->string('status', 20)->default('active')->index()->comment('active | expired | superseded');
            $table->timestamps();
            

            // Indexes
            $table->index(['product_id', 'document_type_id', 'status'], 'idx_product_compliance_active');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('product_compliances');
    }
};