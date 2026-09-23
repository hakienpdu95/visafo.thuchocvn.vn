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
        if (Schema::hasTable('menu_dishes')) {
            return;
        }

        Schema::create('menu_dishes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('dish_name', 255)->comment('Tên món ăn');
            $table->string('main_ingredients', 500)->nullable()->comment('Nguyên liệu chính');
            $table->unsignedInteger('servings')->nullable()->comment('Số suất ăn');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['menu_id', 'sort_order'], 'idx_menu_dishes_menu_sort');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_dishes');
    }
};