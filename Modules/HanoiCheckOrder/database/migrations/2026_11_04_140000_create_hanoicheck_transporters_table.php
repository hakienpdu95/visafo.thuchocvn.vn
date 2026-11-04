<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hanoicheck_transporters')) {
            return;
        }

        Schema::create('hanoicheck_transporters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 255)->unique()->comment('ma_nhan_su gửi lên HanoiCheck (users/merge) — giữ ổn định, không đổi');
            $table->string('name', 255);
            $table->string('phone', 20)->nullable();
            $table->string('cccd', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address', 255)->nullable();
            $table->string('transport_mean', 255)->nullable();
            $table->string('license_plate', 255);
            $table->char('employee_id', 26)->nullable()->index()->comment('Nhân viên VISAFO tương ứng (employees.id) — tùy chọn, không FK');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hanoicheck_transporters');
    }
};
