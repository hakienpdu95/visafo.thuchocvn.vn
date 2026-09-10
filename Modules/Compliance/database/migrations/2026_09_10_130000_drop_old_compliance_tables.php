<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('vendor_certificates');
        Schema::dropIfExists('product_compliances');
        Schema::dropIfExists('partner_product_compliances');
    }

    public function down(): void
    {
        // Không khôi phục — dữ liệu đã chuyển sang compliance_documents qua
        // app:migrate-old-compliances trước khi bảng cũ bị xoá.
    }
};
