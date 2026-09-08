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
        if (Schema::hasTable('document_master_types')) {
            return;
        }

        Schema::create('document_master_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('code', 60)->unique()->comment('Mã loại giấy tờ');
            $table->string('name', 255)->comment('Tên loại giấy tờ');
            $table->string('applicable_category', 30)->index()->comment('Ngành hàng áp dụng — khớp category_type của products');
            $table->boolean('is_required_issue_date')->default(true)->comment('Bắt buộc nhập ngày cấp');
            $table->boolean('is_required_expiry_date')->default(false)->comment('Bắt buộc nhập ngày hết hạn');
            $table->unsignedSmallInteger('default_validity_months')->nullable()->comment('Số tháng hiệu lực mặc định');
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('document_master_types');
    }
};