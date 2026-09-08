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
        if (Schema::hasTable('organization_settings')) {
            return;
        }

        Schema::create('organization_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete()->comment('Tổ chức');
            $table->string('key', 100)->index()->comment('Khóa cài đặt');
            $table->text('value')->nullable()->comment('Giá trị (serialized nếu cần)');
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'float'])->default('string')->comment('Kiểu dữ liệu giá trị');
            $table->timestamps();
            

            // Indexes
            $table->unique(['organization_id', 'key']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
    }
};