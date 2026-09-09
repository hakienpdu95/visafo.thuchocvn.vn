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
        if (Schema::hasTable('external_orders')) {
            return;
        }

        Schema::create('external_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete()->comment('Tổ chức sở hữu');
            $table->foreignUlid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('external_system', 20)->default('sapo')->index()->comment('sapo | kiotviet');
            $table->string('external_order_code', 100)->comment('Mã đơn hàng bên POS — VD SON00123');
            $table->timestamp('ordered_at')->nullable()->comment('Thời điểm lên đơn theo POS');
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('status', 20)->default('received')->index()->comment('received | processed | failed');
            $table->text('raw_payload')->nullable()->comment('Payload webhook gốc — lưu dạng text thô để đối soát/debug, không phải cột JSON có cấu trúc truy vấn');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'external_system', 'external_order_code'], 'uq_external_orders_org_code');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('external_orders');
    }
};