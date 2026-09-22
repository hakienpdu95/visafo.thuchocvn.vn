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
        if (Schema::hasTable('vendor_farming_steps')) {
            return;
        }

        Schema::create('vendor_farming_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('vendor_id')->constrained('vendors')->cascadeOnDelete()->comment('Nông hộ sở hữu bộ bước canh tác này');
            $table->foreignUlid('partner_product_id')->nullable()->constrained('partner_products')->cascadeOnDelete()->comment('Chỉ hiện khi trồng đúng mặt hàng này — NULL nghĩa là áp dụng chung cho mọi mặt hàng của Nông hộ');
            $table->string('step_name', 100)->comment('Tên công đoạn do Nông hộ/QC tự đặt — VD: Bọc trái ổi');
            $table->string('base_activity_type', 20)->default('cultivation')->index()->comment('Phân loại gốc — chỉ được là cultivation hoặc other, không ảnh hưởng thuật toán ATTP (Readiness)');
            $table->integer('order_index')->default(0)->comment('Thứ tự hiển thị trên App Nông hộ');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index('vendor_id');
            $table->index('partner_product_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_farming_steps');
    }
};