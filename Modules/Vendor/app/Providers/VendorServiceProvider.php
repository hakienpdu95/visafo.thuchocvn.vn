<?php

namespace Modules\Vendor\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Vendor\Console\Commands\CheckExpiringCertificatesCommand;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Models\VendorCertificate;
use Modules\Vendor\Observers\VendorCertificateObserver;
use Modules\Vendor\Policies\VendorPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class VendorServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Vendor';

    protected string $nameLower = 'vendor';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    protected array $commands = [
        CheckExpiringCertificatesCommand::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Vendor::class, VendorPolicy::class);

        VendorCertificate::observe(VendorCertificateObserver::class);
    }

    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('vendor:certificates-expiry-check')
            ->name('vendor:certificates-expiry-check')
            ->dailyAt('00:00')
            ->onOneServer();
    }
}
