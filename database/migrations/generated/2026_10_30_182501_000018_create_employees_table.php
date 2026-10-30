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
        if (Schema::hasTable('employees')) {
            return;
        }

        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('full_name', 150)->comment('Họ và tên');
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('facebook_url', 255)->nullable();
            $table->string('job_title', 100)->nullable()->index()->comment('Vai trò công việc — VD: Đầu bếp, Phụ bếp, Nhân viên kho, Lái xe');
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};