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
        if (Schema::hasTable('inbound_receipt_documents')) {
            return;
        }

        Schema::create('inbound_receipt_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('inbound_receipt_id')->constrained('inbound_receipts')->cascadeOnDelete();
            $table->string('document_code', 30)->index()->comment('customs_declaration | co_cert | vat_invoice');
            $table->string('document_number', 150)->comment('Số tờ khai / số C-O / số hóa đơn');
            $table->string('file_url', 500)->nullable()->comment('File scan chứng từ');
            $table->timestamps();
            

            // Indexes
            $table->index(['inbound_receipt_id', 'document_code'], 'idx_inbound_receipt_doc_type');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_receipt_documents');
    }
};