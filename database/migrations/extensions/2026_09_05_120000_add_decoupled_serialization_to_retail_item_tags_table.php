<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('retail_item_tags', function (Blueprint $table) {
            if (! Schema::hasColumn('retail_item_tags', 'uid')) {
                $table->string('uid', 20)->nullable()->unique()->after('id')
                    ->comment('Token công khai dùng trên URL truy xuất — ngẫu nhiên, chống đoán nhận');
            }
            if (! Schema::hasColumn('retail_item_tags', 'gs1_serial')) {
                $table->string('gs1_serial', 20)->nullable()->unique()->after('uid')
                    ->comment('Số sê-ri chuẩn GS1 (AI 21) — duy nhất toàn hệ thống');
            }
            if (! Schema::hasColumn('retail_item_tags', 'visual_sequence')) {
                $table->unsignedBigInteger('visual_sequence')->nullable()->after('gs1_serial')
                    ->comment('Số thứ tự in trực quan trên cuộn tem — dải liên tục toàn hệ thống');
            }
        });

        if (! Schema::hasIndex('retail_item_tags', 'idx_retail_tag_visual_sequence')) {
            Schema::table('retail_item_tags', function (Blueprint $table) {
                $table->index('visual_sequence', 'idx_retail_tag_visual_sequence');
            });
        }

        Schema::table('retail_item_tags', function (Blueprint $table) {
            $table->foreignUlid('batch_id')->nullable()->change();
            $table->foreignUlid('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('retail_item_tags', function (Blueprint $table) {
            if (Schema::hasColumn('retail_item_tags', 'visual_sequence')) {
                $table->dropIndex('idx_retail_tag_visual_sequence');
                $table->dropColumn('visual_sequence');
            }
            if (Schema::hasColumn('retail_item_tags', 'gs1_serial')) {
                $table->dropColumn('gs1_serial');
            }
            if (Schema::hasColumn('retail_item_tags', 'uid')) {
                $table->dropColumn('uid');
            }
        });
    }
};
