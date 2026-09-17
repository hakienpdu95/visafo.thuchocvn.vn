<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('compliance_warnings');
    }

    public function down(): void
    {
        // Không khôi phục — tính năng Cảnh báo pháp lý & hạn dùng đã bị gỡ bỏ hoàn toàn.
    }
};
