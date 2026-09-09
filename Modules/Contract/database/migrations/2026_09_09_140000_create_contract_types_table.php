<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_types')) {
            return;
        }

        Schema::create('contract_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 60)->unique()->comment('framework_agreement | agricultural_offtake | spot_purchase | consignment');
            $table->string('name', 255)->comment('Tên loại hợp đồng');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_types');
    }
};
