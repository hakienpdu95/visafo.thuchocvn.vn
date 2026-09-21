<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Cho phép NCC được tạo nhanh từ Sổ kiểm thực (chưa có MST). Unique vẫn giữ — MySQL cho phép nhiều giá trị NULL. */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('tax_code', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Không ép NOT NULL trở lại: các NCC tạo nhanh sẽ không có MST.
    }
};
