<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('print_logs', 'label_template_id')) {
                $table->foreignUlid('label_template_id')->nullable()->after('order_item_id')
                    ->constrained('label_templates')->nullOnDelete()
                    ->comment('Mẫu tem đã chọn khi in — NULL = theo mẫu gán cho sản phẩm, hoặc mẫu mặc định');
            }
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (Schema::hasColumn('print_logs', 'label_template_id')) {
                $table->dropConstrainedForeignId('label_template_id');
            }
        });
    }
};
