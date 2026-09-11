<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('farming_sources')) {
            return;
        }

        Schema::create('farming_sources', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('vendor_id')->constrained('vendors')->restrictOnDelete()->comment('Nông hộ sở hữu vùng trồng');
            $table->string('source_code', 60)->unique()->comment('Mã vùng trồng (SOURCE_ID)');
            $table->string('name', 255)->comment('Tên/mô tả vùng trồng — VD: Thửa ruộng số 3');
            $table->decimal('area_hectare', 8, 2)->nullable()->comment('Diện tích canh tác (ha)');
            $table->string('water_source', 255)->nullable()->comment('Nguồn nước tưới tiêu');
            $table->string('address', 500)->nullable()->comment('Địa chỉ / vị trí vùng trồng');
            $table->string('status', 20)->default('pending')->index()->comment('pending | passed | failed — trạng thái phê duyệt trước vụ (BM-NH-02)');
            $table->timestamp('pre_season_checked_at')->nullable()->comment('Thời điểm QC xác nhận kiểm tra trước vụ');
            $table->foreignUlid('pre_season_checked_by')->nullable()->constrained('users')->nullOnDelete()->comment('QC đã xác nhận kiểm tra');
            $table->string('notes', 500)->nullable()->comment('Ghi chú');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farming_sources');
    }
};
