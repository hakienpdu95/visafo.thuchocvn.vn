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
        if (Schema::hasTable('product_recalls')) {
            return;
        }

        Schema::create('product_recalls', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete()->comment('SKU bị thu hồi');
            $table->foreignUlid('batch_id')->nullable()->constrained('batches')->restrictOnDelete()->comment('Lô cụ thể bị thu hồi — để trống nghĩa là thu hồi toàn bộ lô của SKU');
            $table->text('reason')->comment('Lý do thu hồi');
            $table->string('severity', 20)->nullable()->index()->comment('minor | major | critical');
            $table->string('status', 20)->default('active')->index()->comment('active | completed | cancelled');
            $table->foreignUlid('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('initiated_at')->useCurrent()->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['product_id', 'status'], 'idx_product_recalls_product_status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recalls');
    }
};