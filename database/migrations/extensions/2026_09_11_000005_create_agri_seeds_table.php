<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('agri_seeds')) {
            return;
        }

        Schema::create('agri_seeds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('crop_type', 100)->index()->comment('Loại cây trồng (VD: Lúa, Ngô, Rau ăn lá)');
            $table->string('name', 255)->index()->comment('Tên giống cây trồng (VD: Khang Dân 18, ST25)');
            $table->string('author_applicant', 500)->nullable()->comment('Tác giả / Cơ quan đăng ký');
            $table->string('decision_number', 255)->nullable()->comment('Số Quyết định / Năm công nhận');
            $table->boolean('is_banned')->default(false)->comment('Cờ đánh dấu giống bị loại khỏi danh mục');
            $table->string('status', 20)->default('active')->index()->comment('active | inactive');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agri_seeds');
    }
};
