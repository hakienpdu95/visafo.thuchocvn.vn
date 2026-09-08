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
        if (Schema::hasTable('outbound_orders')) {
            return;
        }

        Schema::create('outbound_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete()->comment('Tổ chức sở hữu');
            $table->string('order_number', 100)->comment('Mã đơn xuất buôn nội bộ');
            $table->string('order_type', 20)->default('wholesale')->index()->comment('wholesale — dự phòng mở rộng loại khác về sau');
            $table->string('dealer_name', 255)->comment('Tên đại lý/khách buôn');
            $table->string('dealer_phone', 20)->nullable();
            $table->string('dealer_address', 500)->nullable();
            $table->string('status', 20)->default('draft')->index()->comment('draft | completed | cancelled');
            $table->date('ordered_at')->comment('Ngày lập đơn');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'order_number'], 'uq_outbound_orders_org_number');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_orders');
    }
};