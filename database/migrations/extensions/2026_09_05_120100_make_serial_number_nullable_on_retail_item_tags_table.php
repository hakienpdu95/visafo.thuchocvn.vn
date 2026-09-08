<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('retail_item_tags', function (Blueprint $table) {
            $table->unsignedInteger('serial_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('retail_item_tags', function (Blueprint $table) {
            $table->unsignedInteger('serial_number')->nullable(false)->change();
        });
    }
};
