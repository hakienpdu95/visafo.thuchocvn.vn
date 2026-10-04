<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Điền định danh pháp lý + địa chỉ trụ sở cố định (config/company.php) vào bản ghi Trụ sở chính — chỉ các trường đang trống. */
return new class extends Migration
{
    public function up(): void
    {
        $locked = array_filter((array) config('company.locked'), fn ($v) => filled($v));
        if ($locked === []) {
            return;
        }

        $hq = DB::table('internal_facilities')->where('type', 'headquarter')->whereNull('deleted_at')->first();

        if ($hq === null) {
            DB::table('internal_facilities')->insert($locked + [
                'id'         => strtolower((string) Str::ulid()),
                'name'       => 'Trụ sở chính (Công ty)',
                'type'       => 'headquarter',
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $fill = array_filter($locked, fn ($v, $col) => blank($hq->{$col}), ARRAY_FILTER_USE_BOTH);
        if ($fill !== []) {
            DB::table('internal_facilities')->where('id', $hq->id)->update($fill + ['updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Không hoàn tác dữ liệu hồ sơ doanh nghiệp.
    }
};
