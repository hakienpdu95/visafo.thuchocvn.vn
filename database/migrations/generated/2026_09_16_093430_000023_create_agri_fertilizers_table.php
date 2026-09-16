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
        if (Schema::hasTable('agri_fertilizers')) {
            return;
        }

        Schema::create('agri_fertilizers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('category', 100)->index()->comment('Nhóm phân bón (VD: Phân hữu cơ, Phân bón lá...)');
            $table->string('name', 255)->index()->comment('Tên phân bón');
            $table->string('ingredients', 1000)->nullable()->comment('Thành phần và hàm lượng đăng ký');
            $table->string('applicant', 255)->nullable()->comment('Tổ chức đăng ký (APPLICANT)');
            $table->boolean('is_banned')->default(false)->comment('Cờ đánh dấu phân bón bị cấm/loại bỏ');
            $table->string('status', 20)->default('active')->index()->comment('active | inactive');
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('agri_fertilizers');
    }
};