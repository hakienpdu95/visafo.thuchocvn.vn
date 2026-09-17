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
        if (Schema::hasTable('customers')) {
            return;
        }

        Schema::create('customers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('customer_code', 50)->nullable()->comment('Mã khách hàng nội bộ');
            $table->string('name', 255)->comment('Tên pháp nhân ký hợp đồng (VD: Công ty CP Đầu tư Vinakim)');
            $table->string('customer_group', 30)->index()->comment('kindergarten | high_school | industrial_canteen | trading_company');
            $table->string('meal_model', 30)->index()->comment('cook_at_school | pre_cooked_meal | ingredient_supply');
            $table->string('tax_code', 20)->nullable()->comment('Mã số thuế pháp nhân');
            $table->string('address', 500)->nullable()->comment('Địa chỉ trụ sở/trường');
            $table->char('province_code', 2)->nullable();
            $table->foreign('province_code')->references('province_code')->on('provinces')->nullOnDelete();
            $table->char('ward_code', 5)->nullable();
            $table->foreign('ward_code')->references('ward_code')->on('wards')->nullOnDelete();
            $table->string('phone_number', 20)->nullable()->comment('Điện thoại tổng đài');
            $table->string('email', 150)->nullable()->comment('Email tổng đài/hóa đơn');
            $table->string('representative_name', 100)->nullable()->comment('Người đại diện theo pháp luật');
            $table->string('representative_title', 100)->nullable()->comment('Chức danh người đại diện');
            $table->string('representative_phone', 20)->nullable();
            $table->string('representative_email', 100)->nullable();
            $table->foreignUlid('pic_id')->nullable()->constrained('employees')->nullOnDelete()->comment('NV phụ trách (Sales/Account Manager)');
            $table->string('status', 20)->default('active')->index()->comment('active | inactive');
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};