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
        if (Schema::hasTable('internal_facilities')) {
            return;
        }

        Schema::create('internal_facilities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('name', 150)->comment('Tên cơ sở — VD: Trụ sở chính, Vùng trồng Chùa Dâu, Kho Linh Đàm');
            $table->string('type', 30)->default('headquarter')->index()->comment('headquarter | farm | warehouse | processing_zone');
            $table->string('address', 500)->nullable()->comment('Địa chỉ cơ sở');
            $table->string('status', 20)->default('active')->index()->comment('active | inactive');
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_facilities');
    }
};