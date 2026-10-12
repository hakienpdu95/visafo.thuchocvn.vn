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
        if (Schema::hasTable('food_sample_details')) {
            return;
        }

        Schema::create('food_sample_details', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('log_id')->constrained('food_sample_logs')->cascadeOnDelete();
            $table->foreignUlid('step3_detail_id')->nullable()->constrained('food_inspection_step3_details')->nullOnDelete()->comment('Dòng Sổ Bước 3 mà mẫu này kế thừa');
            $table->foreignUlid('menu_dish_id')->nullable()->constrained('menu_dishes')->nullOnDelete()->comment('Món trong Thực đơn (khi bữa ăn chưa có Sổ Bước 3)');
            $table->unsignedSmallInteger('line_no')->nullable();
            $table->string('meal_time', 20)->comment('Bữa ăn: breakfast | lunch | afternoon | dinner');
            $table->string('dish_name', 255)->comment('Tên mẫu thức ăn');
            $table->unsignedInteger('portion_qty')->nullable()->comment('Số suất ăn của món');
            $table->string('sample_volume', 20)->comment('Khối lượng/thể tích mẫu: tối thiểu 100g (đặc) hoặc 150ml (lỏng)');
            $table->string('container_type', 100)->nullable()->comment('Dụng cụ lưu mẫu (có nắp đậy kín)');
            $table->decimal('storage_temp', 4, 1)->nullable()->comment('Nhiệt độ bảo quản (°C) — chuẩn 2–8°C');
            $table->dateTime('sampled_at')->comment('Thời gian lấy mẫu');
            $table->string('sampler_name', 255)->comment('Người lấy mẫu');
            $table->dateTime('destroyed_at')->nullable()->comment('Thời gian hủy mẫu — phải ≥ sampled_at + 24 giờ');
            $table->string('destroyer_name', 255)->nullable()->comment('Người hủy mẫu');
            $table->string('quality_note', 255)->nullable()->comment('Đánh giá chất lượng/cảm quan khi hủy');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['log_id', 'line_no'], 'idx_food_sample_details_log_line');
            $table->index(['sampled_at', 'destroyed_at'], 'idx_food_sample_details_due');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('food_sample_details');
    }
};