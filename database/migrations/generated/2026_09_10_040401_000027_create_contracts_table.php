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
        if (Schema::hasTable('contracts')) {
            return;
        }

        Schema::create('contracts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('vendor_id')->constrained('vendors')->restrictOnDelete()->comment('Nhà cung cấp ký hợp đồng');
            $table->foreignUlid('contract_type_id')->constrained('contract_types')->restrictOnDelete();
            $table->string('contract_number', 100)->comment('Số hợp đồng');
            $table->string('name', 255)->comment('Tên hợp đồng');
            $table->decimal('total_value', 15, 2)->nullable()->comment('Giá trị hợp đồng — NULL với hợp đồng nguyên tắc không định giá trị tổng');
            $table->date('start_date');
            $table->date('end_date')->nullable()->comment('NULL = hợp đồng vô thời hạn');
            $table->boolean('is_auto_renew')->default(false)->comment('Tự động gia hạn khi hết hạn');
            $table->unsignedSmallInteger('renewal_period_months')->nullable()->comment('Số tháng gia hạn thêm mỗi chu kỳ — bắt buộc khi is_auto_renew=true');
            $table->string('status', 20)->default('active')->index()->comment('active | expired | terminated');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique('contract_number', 'contracts_contract_number_unique');
            $table->index(['status', 'end_date'], 'idx_contracts_status_end_date');
            $table->index(['is_auto_renew', 'status', 'end_date'], 'idx_contracts_renewal_scan');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};