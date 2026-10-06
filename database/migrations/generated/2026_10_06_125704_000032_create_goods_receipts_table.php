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
        if (Schema::hasTable('goods_receipts')) {
            return;
        }

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('misa_ref_id', 50)->comment('Số phiếu nhập kho MISA (VD: NK9778) — chốt chặn chống import trùng');
            $table->foreignUlid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete()->comment('Khớp theo tên NCC nếu tìm thấy, để trống nếu không khớp được');
            $table->string('supplier_name', 255)->nullable()->comment('Tên NCC nguyên văn lấy từ ô \"Họ và tên người giao\"');
            $table->date('receipt_date')->nullable()->comment('Ngày trên phiếu nhập kho');
            $table->string('source_file_name', 255)->nullable()->comment('Tên file Excel gốc đã import');
            $table->foreignUlid('imported_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người import file');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique('misa_ref_id', 'goods_receipts_misa_ref_id_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};