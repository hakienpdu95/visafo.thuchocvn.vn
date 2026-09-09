<?php

namespace Modules\Employee\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Employee;
use Modules\Employee\Policies\DepartmentPolicy;
use Modules\Employee\Policies\EmployeePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class EmployeeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Employee';

    protected string $nameLower = 'employee';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
    }
}
