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
        if (Schema::hasTable('tag_rolls')) {
            return;
        }

        Schema::create('tag_rolls', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('prefix', 10)->comment('Tiền tố chữ của gs1_serial trong cuộn này — rỗng nếu không đặt');
            $table->unsignedBigInteger('from_sequence');
            $table->unsignedBigInteger('to_sequence');
            $table->unsignedInteger('count');
            $table->ulid('created_by')->nullable()->comment('Người thực hiện in — NULL nếu chạy qua console/CLI');
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['from_sequence', 'to_sequence'], 'idx_tag_rolls_range');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_rolls');
    }
};