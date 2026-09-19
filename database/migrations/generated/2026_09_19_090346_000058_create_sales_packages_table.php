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
        if (Schema::hasTable('sales_packages')) {
            return;
        }

        Schema::create('sales_packages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->ulid('ulid');
            $table->foreignUlid('customer_id')->constrained('customers')->restrictOnDelete()->comment('Khách hàng/cơ hội chào hàng');
            $table->string('name', 255)->comment('Tên gói, VD: Gói chào suất ăn bán trú 2026');
            $table->unsignedInteger('version')->default(1)->comment('Phiên bản gói — mỗi lần chốt lại tăng thêm 1');
            $table->unsignedTinyInteger('readiness_score')->default(0)->comment('Điểm sẵn sàng chốt tại thời điểm tạo gói (snapshot)');
            $table->string('status', 20)->default('draft')->index()->comment('draft | finalized | sent');
            $table->string('notes', 1000)->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['customer_id', 'version']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_packages');
    }
};