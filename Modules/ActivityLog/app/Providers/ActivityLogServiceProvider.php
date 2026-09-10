<?php

namespace Modules\ActivityLog\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Modules\ActivityLog\Core\LogEntryBuilder;
use Modules\ActivityLog\Observers\AuditableObserver;
use Modules\Compliance\Models\ComplianceDocument;

class ActivityLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/activitylog.php',
            'activitylog_module'
        );

        $this->app->singleton(LogEntryBuilder::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'activitylog');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\ActivityLog\Actions\PurgeOldLogsAction::class,
            ]);
        }

        $this->registerSchedule();
        $this->registerAuditObservers();
    }

    private function registerAuditObservers(): void
    {
        ComplianceDocument::observe(AuditableObserver::class);
    }

    private function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            // Xóa log cũ mỗi đêm lúc 3 giờ sáng
            $schedule->command('activitylog:purge')
                ->dailyAt('03:00')
                ->name('activitylog:purge')
                ->onOneServer();

            // Meta cache tự expire theo TTL (600s) — không cần flush scheduled.
            // Mỗi org có key riêng: actlog:meta:{orgId}, không có key "actlog:meta:all".
        });
    }
}
