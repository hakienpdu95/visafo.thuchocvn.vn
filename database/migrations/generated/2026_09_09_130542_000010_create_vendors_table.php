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
        if (Schema::hasTable('vendors')) {
            return;
        }

        Schema::create('vendors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('vendor_code', 50)->nullable()->unique()->comment('Mã quản lý nội bộ');
            $table->string('name', 255)->comment('Tên tổ chức/cá nhân theo ĐKKD');
            $table->string('tax_code', 50)->unique()->comment('Mã số thuế');
            $table->string('address', 500)->nullable()->comment('Địa chỉ trụ sở chính');
            $table->string('phone_number', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('representative_name', 100)->nullable()->comment('Người đại diện theo pháp luật');
            $table->string('status', 20)->default('active')->index()->comment('active | inactive');
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};