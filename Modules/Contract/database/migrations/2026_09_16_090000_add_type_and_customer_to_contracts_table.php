<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('type', 20)->default('input')->after('id');
            $table->foreignUlid('customer_id')->nullable()->after('vendor_id')
                ->constrained('customers')->restrictOnDelete();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->ulid('vendor_id')->nullable()->change();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index('type', 'idx_contracts_type');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('idx_contracts_type');
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['type', 'customer_id']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->ulid('vendor_id')->nullable(false)->change();
        });
    }
};
