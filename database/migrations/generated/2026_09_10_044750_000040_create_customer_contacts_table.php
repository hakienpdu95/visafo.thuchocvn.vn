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
        if (Schema::hasTable('customer_contacts')) {
            return;
        }

        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name', 100)->comment('Họ và tên');
            $table->string('title', 100)->nullable()->comment('Chức vụ — VD: Kế toán, Bếp trưởng, Quản lý cơ sở');
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contacts');
    }
};