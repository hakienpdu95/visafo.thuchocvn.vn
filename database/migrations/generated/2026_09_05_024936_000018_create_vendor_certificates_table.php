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
        if (Schema::hasTable('vendor_certificates')) {
            return;
        }

        Schema::create('vendor_certificates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('certificate_type', 50)->comment('business_registration | food_safety | gmp');
            $table->string('certificate_number', 100);
            $table->date('issue_date');
            $table->date('expiry_date')->nullable()->index();
            $table->string('issued_by', 255)->nullable();
            $table->boolean('is_active')->default(true)->index()->comment('Chứng chỉ đang hiệu lực cho certificate_type này');
            $table->date('renewal_deadline')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->index(['vendor_id', 'certificate_type', 'is_active'], 'idx_vendor_cert_active');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_certificates');
    }
};