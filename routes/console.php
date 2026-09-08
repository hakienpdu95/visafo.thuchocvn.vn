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
