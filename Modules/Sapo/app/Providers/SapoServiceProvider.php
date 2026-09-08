<?php

namespace Modules\Sapo\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Sapo\Console\Commands\ReconcileSapoOrdersCommand;
use Modules\Sapo\Console\Commands\SyncSapoProductsCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SapoServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Sapo';

    protected string $nameLower = 'sapo';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    protected array $commands = [
        ReconcileSapoOrdersCommand::class,
        SyncSapoProductsCommand::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();
    }

    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('sapo:reconcile-orders')
            ->name('sapo:reconcile-orders')
            ->dailyAt('23:00')
            ->onOneServer();

        // Lưới an toàn — quét toàn bộ danh mục để bù các webhook products/* bị thất lạc
        // trong ngày (Sapo down, timeout, mất kết nối...).
        $schedule->command('sapo:sync-products')
            ->name('sapo:sync-products')
            ->dailyAt('02:00')
            ->onOneServer()
            ->withoutOverlapping();
    }
}
