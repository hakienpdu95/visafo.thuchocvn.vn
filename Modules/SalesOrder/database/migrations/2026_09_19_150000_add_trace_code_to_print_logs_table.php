<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('print_logs', 'trace_code')) {
                $table->string('trace_code', 16)->nullable()->unique('print_logs_trace_code_unique')->after('id')
                    ->comment('Mã truy xuất công khai (ngẫu nhiên) dùng trong QR — không lộ ID tuần tự');
            }
        });

        // Backfill cho các log đã in trước khi có cột này.
        DB::table('print_logs')->whereNull('trace_code')->orderBy('id')->each(function ($row): void {
            do {
                $code = Str::lower(Str::random(10));
            } while (DB::table('print_logs')->where('trace_code', $code)->exists());

            DB::table('print_logs')->where('id', $row->id)->update(['trace_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (Schema::hasColumn('print_logs', 'trace_code')) {
                $table->dropUnique('print_logs_trace_code_unique');
                $table->dropColumn('trace_code');
            }
        });
    }
};
