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
        if (Schema::hasTable('departments')) {
            return;
        }

        Schema::create('departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('name', 150)->comment('Tên phòng ban');
            $table->boolean('is_food_contact')->default(false)->index()->comment('Trực tiếp tiếp xúc thực phẩm — bật kiểm soát hồ sơ y tế gắt gao hơn cho nhân viên thuộc phòng ban này');
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};