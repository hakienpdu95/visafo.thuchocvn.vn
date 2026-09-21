<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menus')) {
            return;
        }

        Schema::create('menus', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('customer_id')->constrained('customers')->restrictOnDelete()
                ->comment('Doanh nghiệp suất ăn / cơ sở được lập thực đơn (Master Data Khách hàng)');
            $table->date('menu_date')->comment('Ngày áp dụng thực đơn');
            $table->string('meal_time', 20)->comment('Bữa ăn: breakfast | lunch | afternoon | dinner');
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'menu_date', 'meal_time'], 'idx_menus_customer_date_meal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
