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
        if (Schema::hasTable('customer_product')) {
            return;
        }

        Schema::create('customer_product', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('status', 20)->default('active')->index()->comment('active | inactive');
            $table->timestamps();
            

            // Indexes
            $table->unique(['customer_id', 'product_id'], 'uq_customer_product');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_product');
    }
};