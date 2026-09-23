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
        if (Schema::hasTable('food_inspection_step3_details')) {
            return;
        }

        Schema::create('food_inspection_step3_details', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('log_id')->constrained('food_inspection_step3_logs')->cascadeOnDelete();
            $table->foreignUlid('step2_detail_id')->nullable()->constrained('food_inspection_step2_details')->nullOnDelete()->comment('Dòng Sổ Bước 2 (món đã nấu, đạt) mà dòng này kế thừa');
            $table->foreignUlid('menu_dish_id')->nullable()->constrained('menu_dishes')->nullOnDelete()->comment('Món trong Thực đơn (kể cả món ăn sẵn/tráng miệng không qua Bước 2)');
            $table->unsignedSmallInteger('line_no')->nullable();
            $table->string('meal_time', 20)->comment('Ca/bữa ăn: breakfast | lunch | afternoon | dinner');
            $table->string('dish_name', 255)->comment('Tên món ăn');
            $table->unsignedInteger('quantity')->nullable()->comment('Số suất ăn');
            $table->time('portion_time')->nullable()->comment('Thời gian chia món ăn xong');
            $table->time('eat_time')->nullable()->comment('Thời gian bắt đầu ăn');
            $table->string('equipment_used', 255)->nullable()->comment('Dụng cụ chứa đựng / bảo quản khi vận chuyển');
            $table->boolean('sensory_eval')->default(true)->comment('Đánh giá cảm quan');
            $table->text('action_taken')->nullable()->comment('Biện pháp xử lý — bắt buộc khi cảm quan không đạt');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['log_id', 'line_no'], 'idx_fi_step3_details_log_line');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('food_inspection_step3_details');
    }
};