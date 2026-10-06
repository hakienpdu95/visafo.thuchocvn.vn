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
        if (Schema::hasTable('batch_quality_checks')) {
            return;
        }

        Schema::create('batch_quality_checks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('product_batch_id')->nullable()->constrained('product_batches')->cascadeOnDelete()->comment('Lô nhập kho được kiểm — bắt buộc với receiving/sensory');
            $table->foreignUlid('sales_order_item_id')->nullable()->constrained('sales_order_items')->cascadeOnDelete()->comment('Dòng đơn bán được kiểm trước xuất — bắt buộc với pre_dispatch');
            $table->string('stage', 20)->comment('receiving | sensory | pre_dispatch');
            $table->string('result', 10)->comment('pass | fail');
            $table->dateTime('checked_at');
            $table->foreignUlid('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['product_batch_id', 'stage']);
            $table->index(['sales_order_item_id', 'stage']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_quality_checks');
    }
};