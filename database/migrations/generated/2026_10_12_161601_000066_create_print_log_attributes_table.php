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
        if (Schema::hasTable('print_log_attributes')) {
            return;
        }

        Schema::create('print_log_attributes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('print_log_id')->constrained('print_logs')->cascadeOnDelete()->comment('Lần in tem sở hữu thông tin này');
            $table->string('attribute_key', 100)->comment('Tên thông tin in trên tem');
            $table->text('attribute_value')->nullable()->comment('Nội dung thông tin in trên tem');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index('print_log_id', 'idx_print_log_attributes_log');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('print_log_attributes');
    }
};