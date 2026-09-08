<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            return;
        }

        Schema::create('customers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete()->comment('Tổ chức sở hữu');
            $table->string('name', 255)->comment('Tên khách hàng');
            $table->string('phone', 20)->nullable()->index()->comment('Số điện thoại — khóa đối soát chính khi đồng bộ từ POS');
            $table->string('email', 150)->nullable();
            $table->string('external_system', 20)->nullable()->index()->comment('Hệ thống nguồn — sapo | kiotviet');
            $table->string('external_system_id', 100)->nullable()->index()->comment('ID khách hàng bên hệ thống POS');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'phone'], 'uq_customers_org_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
