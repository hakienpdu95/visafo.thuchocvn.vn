<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('label_templates')) {
            return;
        }

        Schema::create('label_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name', 255)->comment('Tên mẫu tem, VD: Tem Thực Phẩm Chức Năng 60x40');
            $table->string('view_path', 255)->comment('Đường dẫn Blade view của mẫu tem, VD: labels.templates.functional_food');
            $table->text('description')->nullable()->comment('Ghi chú các attribute động mẫu hỗ trợ');
            $table->string('default_size', 50)->nullable()->comment('Kích thước tem mặc định, VD: 60x40 mm');

            $table->timestamps();
            $table->softDeletes();

            $table->index('name', 'idx_label_templates_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_templates');
    }
};
