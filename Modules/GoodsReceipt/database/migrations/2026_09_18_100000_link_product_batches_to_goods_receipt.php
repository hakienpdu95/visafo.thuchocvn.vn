<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('product_batches', 'goods_receipt_id')) {
            Schema::table('product_batches', function (Blueprint $table) {
                $table->foreignUlid('goods_receipt_id')->nullable()->after('id')
                    ->constrained('goods_receipts')->cascadeOnDelete();
            });
        }

        DB::table('product_batches')
            ->join('goods_receipt_items', 'goods_receipt_items.id', '=', 'product_batches.goods_receipt_item_id')
            ->whereNull('product_batches.goods_receipt_id')
            ->update(['product_batches.goods_receipt_id' => DB::raw('goods_receipt_items.goods_receipt_id')]);

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropForeign('product_batches_goods_receipt_item_id_foreign');
            $table->dropUnique('product_batches_goods_receipt_item_id_unique');
            $table->dropColumn('goods_receipt_item_id');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->foreignUlid('goods_receipt_id')->nullable(false)->change();
            $table->unique(['product_id', 'goods_receipt_id'], 'product_batches_product_goods_receipt_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropUnique('product_batches_product_goods_receipt_unique');
            $table->dropForeign(['goods_receipt_id']);
            $table->dropColumn('goods_receipt_id');

            $table->foreignUlid('goods_receipt_item_id')->after('product_id')
                ->constrained('goods_receipt_items')->cascadeOnDelete()->unique();
        });
    }
};
