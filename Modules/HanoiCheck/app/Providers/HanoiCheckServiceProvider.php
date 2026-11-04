<?php

namespace Modules\HanoiCheck\Providers;

use Modules\HanoiCheck\Console\Commands\HanoiCheckLookupCommand;
use Modules\HanoiCheck\Console\Commands\HanoiCheckSyncCommand;
use Modules\HanoiCheck\Console\Commands\HanoiCheckTokenCommand;
use Modules\HanoiCheck\Enums\SyncEntity;
use Modules\HanoiCheck\Observers\HanoiCheckObserver;
use Modules\HanoiCheck\Support\HanoiCheckDispatchGuard;
use Nwidart\Modules\Support\ModuleServiceProvider;

class HanoiCheckServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'HanoiCheck';

    protected string $nameLower = 'hanoicheck';

    protected array $commands = [
        HanoiCheckTokenCommand::class,
        HanoiCheckLookupCommand::class,
        HanoiCheckSyncCommand::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->scoped(HanoiCheckDispatchGuard::class);
    }

    public function boot(): void
    {
        parent::boot();

        foreach (SyncEntity::cases() as $entity) {
            if ($entity->observed()) {
                $entity->modelClass()::observe(HanoiCheckObserver::class);
            }
        }
    }
}
