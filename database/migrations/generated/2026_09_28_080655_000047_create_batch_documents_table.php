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
        if (Schema::hasTable('batch_documents')) {
            return;
        }

        Schema::create('batch_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->string('document_code', 30)->index()->comment('coa | quality_test');
            $table->string('document_number', 150)->comment('Số hiệu phiếu');
            $table->string('file_url', 500)->nullable()->comment('File scan PDF');
            $table->timestamps();
            

            // Indexes
            $table->index(['batch_id', 'document_code'], 'idx_batch_doc_type');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_documents');
    }
};