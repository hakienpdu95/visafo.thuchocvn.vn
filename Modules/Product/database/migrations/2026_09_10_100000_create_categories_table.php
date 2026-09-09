<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('categories')) {
            return;
        }

        Schema::create('categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 60)->unique()->comment('fresh_food | processed_food | prepackaged_food | additives_spices | functional_fortified_food | beverages_water');
            $table->string('name', 255)->comment('Tên nhóm thực phẩm');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
