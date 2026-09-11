<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('farming_logs')) {
            return;
        }

        Schema::create('farming_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('farming_batch_id')->constrained('farming_batches')->cascadeOnDelete()->comment('Vụ/lô ghi nhận hoạt động này');
            $table->string('activity_type', 30)->index()->comment('cultivation | water | fertilizer | pesticide | harvest');
            $table->dateTime('activity_date')->index()->comment('Ngày giờ thực hiện hoạt động — cần chính xác đến giờ/phút cho phun thuốc (NK-NH-05) và thu hoạch (NK-NH-06)');
            $table->json('details')->nullable()->comment('Chi tiết theo activity_type — fertilizer: {agri_fertilizer_id,quantity,unit}; pesticide: {agri_pesticide_id,quantity,unit}; harvest: {quantity,unit}');
            $table->date('safe_harvest_date')->nullable()->index()->comment('Ngày an toàn thu hoạch — chỉ có ở log pesticide, = activity_date + quarantine_days của thuốc đã chọn');
            $table->string('image_path', 255)->nullable()->comment('Đường dẫn ảnh chụp minh chứng (vỏ thuốc BVTV, bao phân bón, hiện trường)');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người ghi nhật ký');
            $table->string('notes', 500)->nullable()->comment('Ghi chú');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farming_logs');
    }
};
