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
        if (Schema::hasTable('farming_batches')) {
            return;
        }

        Schema::create('farming_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('farming_source_id')->constrained('farming_sources')->restrictOnDelete()->comment('Vùng trồng thực hiện vụ này');
            $table->foreignUlid('vendor_id')->constrained('vendors')->restrictOnDelete()->comment('Nông hộ — denormalize từ farming_source_id để tăng tốc Query Báo cáo Truy xuất nguồn gốc');
            $table->foreignUlid('agri_seed_id')->constrained('agri_seeds')->restrictOnDelete()->comment('Giống cây trồng — bắt buộc chọn từ Từ điển Giống cây trồng');
            $table->foreignUlid('partner_product_id')->constrained('partner_products')->restrictOnDelete()->comment('Mặt hàng thương mại tương ứng mà Visafo thu mua — nối luồng Nông hộ - Mặt hàng - Vụ/Lô - Kho Visafo (TX4)');
            $table->string('batch_code', 60)->unique()->comment('Mã lô (LOT_ID) — VD: SP-202609-01');
            $table->date('sowing_date')->nullable()->index()->comment('Ngày gieo / xuống giống');
            $table->date('expected_harvest_date')->nullable()->comment('Ngày dự kiến thu hoạch');
            $table->date('actual_harvest_date')->nullable()->comment('Ngày thu hoạch thực tế');
            $table->string('status', 20)->default('active')->index()->comment('active | harvested | cancelled');
            $table->string('pre_harvest_status', 20)->default('pending')->index()->comment('pending | passed | blocked — kết quả kiểm tra Readiness trước thu hoạch (BM-NH-04, B8)');
            $table->timestamp('pre_harvest_checked_at')->nullable()->comment('Thời điểm QC phê duyệt cho phép thu hoạch');
            $table->foreignUlid('pre_harvest_checked_by')->nullable()->constrained('users')->nullOnDelete()->comment('QC đã phê duyệt cho phép thu hoạch');
            $table->string('notes', 500)->nullable()->comment('Ghi chú');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index('farming_source_id');
            $table->index('vendor_id');
            $table->index('agri_seed_id');
            $table->index('partner_product_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('farming_batches');
    }
};