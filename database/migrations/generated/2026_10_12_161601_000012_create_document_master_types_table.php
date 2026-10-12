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
            $table->string('document_group', 30)->nullable()->index()->comment('Nhóm giấy tờ ATTP — pháp lý cơ sở | nhân viên | truy xuất nguồn gốc | sổ sách giám sát');
            $table->json('applicable_to')->nullable()->comment('Đối tượng áp dụng — mảng gồm vendor | product | partner_product | internal');
            $table->boolean('is_required_issue_date')->default(true)->comment('Bắt buộc nhập ngày cấp');
            $table->boolean('is_required_expiry_date')->default(false)->comment('Bắt buộc nhập ngày hết hạn');
            $table->boolean('has_expiration_date')->default(true)->comment('Loại giấy tờ này có theo dõi ngày hết hạn không — điều khiển ẩn/hiện field trên form');
            $table->boolean('has_issue_place')->default(true)->comment('Loại giấy tờ này có nơi cấp không — điều khiển ẩn/hiện field trên form');
            $table->boolean('is_transactional')->default(false)->comment('Chứng từ phát sinh theo lô/chuyến hàng (phiếu xuất nhập, kiểm dịch...)');
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