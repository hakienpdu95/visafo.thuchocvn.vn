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
        if (Schema::hasTable('food_inspection_step2_details')) {
            return;
        }

        Schema::create('food_inspection_step2_details', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('log_id')->constrained('food_inspection_step2_logs')->cascadeOnDelete();
            $table->foreignUlid('menu_dish_id')->nullable()->constrained('menu_dishes')->nullOnDelete()->comment('Món ăn trong Thực đơn đã đồng bộ (NULL = dòng nhập tay)');
            $table->unsignedSmallInteger('line_no')->nullable();
            $table->string('meal_time', 20)->comment('Ca/bữa ăn: breakfast | lunch | afternoon | dinner');
            $table->string('dish_name', 255)->comment('Tên món ăn');
            $table->string('main_ingredients', 500)->nullable()->comment('Nguyên liệu chính');
            $table->unsignedInteger('quantity')->nullable()->comment('Số suất ăn');
            $table->time('prep_time')->nullable()->comment('Thời gian sơ chế xong');
            $table->time('cook_time')->nullable()->comment('Thời gian chế biến xong');
            $table->boolean('hygiene_personnel')->default(true)->comment('Vệ sinh người tham gia chế biến');
            $table->boolean('hygiene_equipment')->default(true)->comment('Vệ sinh trang thiết bị, dụng cụ');
            $table->boolean('hygiene_area')->default(true)->comment('Vệ sinh khu vực chế biến');
            $table->boolean('sensory_eval')->default(true)->comment('Đánh giá cảm quan');
            $table->text('action_taken')->nullable()->comment('Biện pháp xử lý — bắt buộc khi có tiêu chí không đạt');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['log_id', 'line_no'], 'idx_fi_step2_details_log_line');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('food_inspection_step2_details');
    }
};