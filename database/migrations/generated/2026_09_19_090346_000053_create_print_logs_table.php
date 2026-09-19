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
        if (Schema::hasTable('print_logs')) {
            return;
        }

        Schema::create('print_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('order_item_id')->constrained('sales_order_items')->cascadeOnDelete()->comment('Dòng đơn xuất hàng đã in tem');
            $table->decimal('weight_per_label', 10, 3)->comment('Khối lượng (kg) trên mỗi tem');
            $table->unsignedSmallInteger('label_count')->comment('Số tem đã in trong lần này');
            $table->date('mfg_date')->nullable()->comment('NSX in trên tem');
            $table->date('exp_date')->comment('HSD in trên tem');
            $table->string('supplier_name', 255)->nullable()->comment('Nguồn cung in trên tem');
            $table->foreignUlid('printed_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người thực hiện in tem');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index('order_item_id', 'idx_print_logs_order_item');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('print_logs');
    }
};