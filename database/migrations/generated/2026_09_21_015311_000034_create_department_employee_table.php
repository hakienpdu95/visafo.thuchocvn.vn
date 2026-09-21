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
        if (Schema::hasTable('department_employee')) {
            return;
        }

        Schema::create('department_employee', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->unique(['department_id', 'employee_id'], 'uniq_department_employee');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('department_employee');
    }
};