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
        if (Schema::hasTable('trace_reviews')) {
            return;
        }

        Schema::create('trace_reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('trace_code', 32)->index()->comment('Mã TXNG khách quét — bắt buộc, để truy ngược lô khi có sự cố');
            $table->foreignUlid('print_log_id')->nullable()->constrained('print_logs')->nullOnDelete();
            $table->foreignUlid('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete()->comment('Nội suy từ trace_code → \"Đã xác minh giao dịch\"');
            $table->foreignUlid('product_id')->nullable()->constrained('products')->nullOnDelete()->comment('Gom điểm trung bình theo sản phẩm');
            $table->foreignUlid('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('type', 10)->index()->comment('rating | issue');
            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->unsignedTinyInteger('delivery_score')->nullable();
            $table->unsignedTinyInteger('packaging_score')->nullable();
            $table->unsignedTinyInteger('traceability_score')->nullable();
            $table->string('issue_category', 30)->nullable()->comment('Chỉ với type=issue');
            $table->text('comment')->nullable();
            $table->boolean('is_public_requested')->default(false)->comment('Khách đồng ý cho công khai nhận xét');
            $table->string('status', 10)->default('pending')->index()->comment('pending | approved | rejected');
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['product_id', 'type', 'status']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('trace_reviews');
    }
};