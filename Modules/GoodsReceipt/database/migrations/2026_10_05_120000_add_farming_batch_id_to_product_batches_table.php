<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_batches', 'farming_batch_id')) {
            return;
        }

        Schema::table('product_batches', function (Blueprint $table) {
            $table->foreignUlid('farming_batch_id')->nullable()->after('goods_receipt_id')
                ->constrained('farming_batches')->nullOnDelete()
                ->comment('Lô canh tác nguồn (nhật ký đồng ruộng) — trang truy xuất đi theo khóa này, không đoán theo mã lô');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_batches', 'farming_batch_id')) {
            return;
        }

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('farming_batch_id');
        });
    }
};
