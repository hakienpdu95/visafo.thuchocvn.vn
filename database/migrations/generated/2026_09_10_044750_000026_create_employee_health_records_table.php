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
        if (Schema::hasTable('employee_health_records')) {
            return;
        }

        Schema::create('employee_health_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('record_type', 20)->index()->comment('health_check | attp_training');
            $table->date('issue_date')->comment('Ngày khám / ngày cấp');
            $table->date('expiry_date')->nullable()->index()->comment('Ngày hết hạn — phục vụ cronjob cảnh báo');
            $table->string('result', 30)->nullable()->comment('Kết luận khám sức khỏe — qualified | not_qualified');
            $table->string('certificate_number', 100)->nullable()->comment('Số giấy xác nhận ATTP');
            $table->string('issued_by', 255)->nullable()->comment('Cơ quan cấp — ATTP');
            $table->text('notes')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->index(['employee_id', 'record_type', 'issue_date'], 'idx_employee_health_latest');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_health_records');
    }
};