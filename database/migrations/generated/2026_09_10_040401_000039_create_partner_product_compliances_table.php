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
        if (Schema::hasTable('partner_product_compliances')) {
            return;
        }

        Schema::create('partner_product_compliances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('partner_product_id')->constrained('partner_products')->cascadeOnDelete();
            $table->foreignUlid('document_type_id')->constrained('document_master_types')->restrictOnDelete();
            $table->string('document_number', 150)->comment('Số tiếp nhận / số chứng nhận');
            $table->date('issue_date')->nullable()->comment('Ngày cấp');
            $table->date('expiration_date')->nullable()->index()->comment('Ngày hết hạn — phục vụ cronjob cảnh báo');
            $table->string('file_url', 500)->nullable()->comment('File scan bản chính');
            $table->string('status', 20)->default('active')->index()->comment('active | expired | superseded');
            $table->timestamps();
            

            // Indexes
            $table->index(['partner_product_id', 'document_type_id', 'status'], 'idx_partner_product_compliance_active');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_product_compliances');
    }
};