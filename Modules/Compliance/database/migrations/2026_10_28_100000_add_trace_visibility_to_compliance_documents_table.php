<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_documents', function (Blueprint $table) {
            $table->string('trace_visibility', 10)->default('status')->after('status')
                ->comment('full | status | hidden — mức công khai hồ sơ lô hàng trên trang truy xuất');
        });

        DB::table('compliance_documents')->update(['trace_visibility' => 'full']);
    }

    public function down(): void
    {
        Schema::table('compliance_documents', function (Blueprint $table) {
            $table->dropColumn('trace_visibility');
        });
    }
};
