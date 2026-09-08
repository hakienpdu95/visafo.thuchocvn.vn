<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tag_rolls')) {
            return;
        }

        Schema::create('tag_rolls', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('prefix', 10)->comment('Tiền tố chữ của gs1_serial trong cuộn này — rỗng nếu không đặt');
            $table->unsignedBigInteger('from_sequence');
            $table->unsignedBigInteger('to_sequence');
            $table->unsignedInteger('count');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['organization_id', 'created_at'], 'idx_tag_rolls_org_created');
            $table->index(['from_sequence', 'to_sequence'], 'idx_tag_rolls_range');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_rolls');
    }
};
