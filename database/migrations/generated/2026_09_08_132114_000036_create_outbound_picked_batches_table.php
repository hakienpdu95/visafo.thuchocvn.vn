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
        if (Schema::hasTable('outbound_picked_batches')) {
            return;
        }

        Schema::create('outbound_picked_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('outbound_order_id')->constrained('outbound_orders')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete()->comment('Denormalize để query nhanh');
            $table->foreignUlid('batch_id')->constrained('batches')->restrictOnDelete();
            $table->unsignedInteger('quantity')->comment('Số lượng lấy từ lô này');
            $table->timestamps();
            

            // Indexes
            $table->index('outbound_order_id', 'idx_picked_batches_order');
            $table->index('batch_id', 'idx_picked_batches_batch');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_picked_batches');
    }
};