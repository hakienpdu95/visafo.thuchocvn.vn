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
        if (Schema::hasTable('inbound_receipts')) {
            return;
        }

        Schema::create('inbound_receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete()->comment('Tổ chức sở hữu');
            $table->foreignUlid('vendor_id')->constrained('vendors')->restrictOnDelete()->comment('Nhà cung cấp giao chuyến hàng này');
            $table->string('receipt_number', 100)->comment('Mã phiếu nhập nội bộ');
            $table->date('received_date')->index()->comment('Ngày nhận hàng');
            $table->string('status', 20)->default('draft')->index()->comment('draft | completed | cancelled');
            $table->text('notes')->nullable()->comment('Ghi chú thêm');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'receipt_number'], 'uq_inbound_receipts_org_number');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_receipts');
    }
};