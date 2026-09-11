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
        if (Schema::hasTable('sapo_product_sync_logs')) {
            return;
        }

        Schema::create('sapo_product_sync_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('source', 20)->comment('webhook | polling');
            $table->string('topic', 40)->comment('products/create | products/update | products/delete');
            $table->string('status', 20)->comment('success | failed');
            $table->string('sapo_product_id', 100)->nullable();
            $table->string('sapo_variant_id', 100)->nullable();
            $table->foreignUlid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name', 255)->nullable();
            $table->string('sku', 100)->nullable();
            $table->text('message')->nullable()->comment('Chi tiết lỗi khi status=failed');
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index('created_at');
            $table->index('status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sapo_product_sync_logs');
    }
};