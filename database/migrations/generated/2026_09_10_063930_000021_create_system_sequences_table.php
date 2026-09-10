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
        if (Schema::hasTable('system_sequences')) {
            return;
        }

        Schema::create('system_sequences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('code_type', 50)->unique()->comment('Mã phân loại bộ đếm — VD: vendor, employee');
            $table->string('prefix', 20)->comment('Tiền tố mã sinh ra — VD: NCC, NV');
            $table->unsignedBigInteger('last_number')->default(0)->comment('Số đếm cuối cùng đã cấp — chỉ tăng, không bao giờ lùi');
            $table->unsignedInteger('padding_length')->default(6)->comment('Độ dài chuỗi số, đệm 0 — VD: 6 => 000001');
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('system_sequences');
    }
};