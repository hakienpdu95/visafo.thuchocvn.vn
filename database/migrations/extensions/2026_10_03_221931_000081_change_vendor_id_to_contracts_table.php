<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->ulid('vendor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // TODO: rollback change cho 'vendor_id'
        });
    }
};