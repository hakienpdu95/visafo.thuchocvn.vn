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
        if (Schema::hasTable('food_inspection_step1_details')) {
            return;
        }

        Schema::create('food_inspection_step1_details', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('log_id')->constrained('food_inspection_step1_logs')->cascadeOnDelete();
            $table->foreignUlid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignUlid('goods_receipt_item_id')->nullable()->constrained('goods_receipt_items')->nullOnDelete();
            $table->foreignUlid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('vendor_name', 255)->comment('Tên cơ sở cung cấp (snapshot, cho phép nhập tay NCC ngoài danh sách)');
            $table->string('deliverer_name', 255)->comment('Tên người giao hàng');
            $table->dateTime('received_at')->comment('Thời gian nhập hàng');
            $table->unsignedSmallInteger('line_no')->nullable();
            $table->string('food_group', 20)->comment('fresh = tươi sống/đông lạnh (Nhóm A) | dry = khô/bao gói/gia vị (Nhóm B)');
            $table->string('product_name', 255)->comment('Tên thực phẩm');
            $table->decimal('quantity', 14, 3)->nullable()->comment('Khối lượng');
            $table->string('unit', 50)->nullable();
            $table->boolean('has_invoice')->nullable()->comment('Chứng từ, hóa đơn (cả 2 nhóm)');
            $table->boolean('has_vet_cert')->nullable()->comment('Giấy đủ điều kiện vệ sinh thú y');
            $table->boolean('has_quarantine_cert')->nullable()->comment('Giấy kiểm dịch');
            $table->string('sensory_result', 10)->default('pass')->comment('Cảm quan (màu, mùi vị, trạng thái): pass | fail');
            $table->string('quick_test_result', 10)->default('none')->comment('Xét nghiệm nhanh: none | pass | fail — chỉ Nhóm A');
            $table->text('handling_measure')->nullable()->comment('Biện pháp xử lý — bắt buộc khi cảm quan hoặc test nhanh không đạt');
            $table->string('manufacturer_name', 255)->nullable()->comment('Tên cơ sở sản xuất');
            $table->string('manufacturer_address', 500)->nullable()->comment('Địa chỉ cơ sở sản xuất');
            $table->date('expiry_date')->nullable()->comment('Hạn sử dụng');
            $table->string('storage_condition', 20)->nullable()->comment('ambient = nhiệt độ thường | cold = lạnh');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['log_id', 'food_group'], 'idx_fi_step1_details_log_group');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('food_inspection_step1_details');
    }
};