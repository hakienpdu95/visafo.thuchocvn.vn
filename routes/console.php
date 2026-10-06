<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Auth\Models\SocialAccount;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lịch chạy cho WorkflowAutomation/KcItem/Sop/JobPosting/Task/BusinessProject/KpiGoal/Survey đã
// bị gỡ cùng các module đó (cleanup/remove-non-competency-modules).

// Media: cleanup Jodit orphan images older than 24h — chạy mỗi 4h
Schedule::command('media:cleanup-orphans')
    ->name('media:cleanup-orphans')
    ->everyFourHours()
    ->onOneServer();

// Passport Phase 0: auto-suspend membership đã quá contract_end_date
Schedule::command('passport:auto-suspend-expired')
    ->name('passport:auto-suspend-expired')
    ->dailyAt('01:00')
    ->onOneServer();

// Passport Phase 0: weekly report thành viên không hoạt động > 45 ngày
Schedule::command('passport:flag-inactive-members')
    ->name('passport:flag-inactive-members')
    ->weeklyOn(1, '08:00')
    ->onOneServer();

// Social Auth: xóa token đã hết hạn > 30 ngày (giảm dữ liệu nhạy cảm lưu trữ)
Schedule::call(function () {
    SocialAccount::where('token_expires_at', '<', now()->subDays(30))->update([
        'access_token'  => null,
        'refresh_token' => null,
    ]);
})->weekly()->name('social-auth:cleanup-expired-tokens')->onOneServer();

Artisan::command('trace:diagnose {code}', function (string $code) {
    $log = \Modules\SalesOrder\Models\PrintLog::query()->where('trace_code', $code)
        ->with(['orderItem.product', 'vendor', 'productBatch.farmingBatch.partnerProduct'])->first();
    if ($log === null) {
        $this->error("Không tìm thấy mã {$code}.");

        return 1;
    }

    $item = $log->orderItem;
    $this->line("Tem {$code}: trạng thái {$log->status->value}");
    $this->line('  Dòng đơn: ' . ($item?->product_name_raw ?? '—') . ' | product_id=' . ($item?->product_id ?? 'NULL') . ' (' . ($item?->product?->name ?? 'không tìm thấy sản phẩm') . ')');
    $this->line('  NCC trên tem: vendor_id=' . ($log->vendor_id ?? 'NULL') . ' (' . ($log->vendor?->name ?? $log->supplier_name ?? '—') . ')');
    $this->line('  Lô nhập: ' . ($log->product_batch_id ?? 'không gắn lô'));

    if ($log->vendor_id === null) {
        $this->warn('→ Tem không gắn NCC nên không có khối Nguồn gốc / Hàng hóa NCC.');

        return 0;
    }

    $rows = \Modules\Product\Models\PartnerProduct::query()->where('vendor_id', $log->vendor_id)->with('product:id,name')->get();
    $this->line("  Hàng hóa của NCC này: {$rows->count()} bản ghi");
    foreach ($rows as $p) {
        $match = (string) $p->product_id === (string) $item?->product_id ? '  ← KHỚP' : '';
        $this->line("    - {$p->name} | ánh xạ product_id=" . ($p->product_id ?? 'NULL') . ' (' . ($p->product?->name ?? '—') . ") | {$p->status?->value}{$match}");
    }

    $found = \Modules\SalesOrder\Queries\GetTraceabilityHandler::partnerProductFor($log);
    $found
        ? $this->info("→ Trang truy xuất dùng hàng hóa NCC: {$found->name} (NSX/Nguồn gốc: " . ($found->manufacturer_name ?: '—') . ', vùng gốc: ' . ($found->origin_address ?: '—') . ')')
        : $this->warn('→ Không tìm được hàng hóa NCC khớp: tem đang gắn NCC khác, hoặc mặt hàng NCC chưa ánh xạ đúng sản phẩm của dòng đơn.');

    return 0;
})->purpose('Chẩn đoán dữ liệu Nguồn gốc / Hàng hóa NCC của một mã truy xuất');
