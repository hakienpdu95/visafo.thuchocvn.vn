<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('batch_attributes')) {
            return;
        }

        Schema::create('batch_attributes', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('batch_id')->constrained('product_batches')->cascadeOnDelete()->comment('Lô hàng sở hữu thông tin bổ sung này');
            $table->string('attribute_key', 100)->comment('Tên thông tin, VD: HDSD, Liều dùng, Bảo quản');
            $table->text('attribute_value')->nullable()->comment('Nội dung thông tin');

            $table->timestamps();
            $table->softDeletes();

            $table->index('batch_id', 'idx_batch_attributes_batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_attributes');
    }
};
