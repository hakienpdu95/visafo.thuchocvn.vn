<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sapo_product_sync_logs')) {
            return;
        }

        Schema::create('sapo_product_sync_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();

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

            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sapo_product_sync_logs');
    }
};
