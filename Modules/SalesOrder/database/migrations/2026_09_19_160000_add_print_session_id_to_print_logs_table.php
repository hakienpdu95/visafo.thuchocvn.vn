<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Định danh từng tem (item-level serialization): mỗi tem vật lý = 1 bản ghi print_logs có trace_code riêng,
 * các tem của cùng một lần in được gom bằng print_session_id. Cột label_count (số tem/bản ghi) không còn cần.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('print_logs', 'print_session_id')) {
                $table->string('print_session_id', 26)->nullable()->index('idx_print_logs_session')->after('trace_code')
                    ->comment('Mã gom nhóm các tem của cùng một lần in');
            }
        });

        if (! Schema::hasColumn('print_logs', 'label_count')) {
            return;
        }

        // Tách các log cũ có label_count > 1 thành từng bản ghi/tem, mỗi bản ghi một trace_code riêng.
        DB::table('print_logs')->where('label_count', '>', 1)->orderBy('id')->each(function ($row): void {
            $attrs = DB::table('print_log_attributes')->where('print_log_id', $row->id)->get();

            for ($i = 1; $i < (int) $row->label_count; $i++) {
                do {
                    $code = Str::lower(Str::random(10));
                } while (DB::table('print_logs')->where('trace_code', $code)->exists());

                $clone = (array) $row;
                $clone['id'] = Str::lower((string) Str::ulid());
                $clone['trace_code'] = $code;
                $clone['print_session_id'] = $row->id;
                $clone['label_count'] = 1;
                DB::table('print_logs')->insert($clone);

                foreach ($attrs as $attr) {
                    $a = (array) $attr;
                    $a['id'] = Str::lower((string) Str::ulid());
                    $a['print_log_id'] = $clone['id'];
                    DB::table('print_log_attributes')->insert($a);
                }
            }
        });

        DB::table('print_logs')->whereNull('print_session_id')->update(['print_session_id' => DB::raw('id')]);

        Schema::table('print_logs', function (Blueprint $table) {
            $table->dropColumn('label_count');
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('print_logs', 'label_count')) {
                $table->unsignedSmallInteger('label_count')->default(1)->after('weight_per_label')
                    ->comment('Số tem đã in trong lần này');
            }
            if (Schema::hasColumn('print_logs', 'print_session_id')) {
                $table->dropIndex(['print_session_id']);
                $table->dropColumn('print_session_id');
            }
        });
    }
};
