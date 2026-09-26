<?php

namespace Modules\User\Http\Controllers;

use App\Enums\PermissionModule;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\User\Actions\DestroyUserAction;
use Modules\User\Actions\StoreUserAction;
use Modules\User\Actions\SyncUserPermissionsAction;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Data\StoreUserData;
use Modules\User\Data\UpdateUserData;
use Modules\Employee\Models\Employee;
use Modules\Vendor\Models\Vendor;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $isAdmin   = $request->user()->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
        $canEdit   = $request->user()->can('create', User::class);
        $canDelete = $isAdmin;

        $counts = User::query()->selectRaw(
            'COUNT(*) as total_all,
             SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as total_active,
             SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as total_inactive'
        )->first();

        $roles = collect(RoleEnum::cases())
            ->map(fn ($r) => ['value' => $r->value, 'text' => $r->label()])
            ->all();

        $statuses = [
            ['value' => '1', 'text' => 'Hoạt động'],
            ['value' => '0', 'text' => 'Vô hiệu'],
        ];

        return view('user::index', compact('isAdmin', 'canEdit', 'canDelete', 'roles', 'statuses', 'counts'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', User::class);

        $isAdmin = $request->user()->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);

        $roles     = $this->buildRolesFor($request->user());
        $permGrid  = $this->permissionGrid($request->user(), $roles);
        $vendors   = Vendor::query()->orderBy('name')->get(['id', 'name']);
        $employees = Employee::query()->whereDoesntHave('user')->orderBy('full_name')->get(['id', 'full_name']);

        return view('user::create', compact('roles', 'permGrid', 'isAdmin', 'vendors', 'employees'));
    }

    public function store(Request $request, StoreUserAction $action): RedirectResponse
    {
        $this->authorize('create', User::class);

        $this->guardRoleEscalation($request->user(), $request->input('system_role'));

        $data = StoreUserData::validateAndCreate($request->all());
        $user = $action->handle($data);

        return redirect()->route('backend.users.index')
            ->with('success', 'Tài khoản "' . $user->name . '" đã được tạo thành công.');
    }

    public function edit(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $isAdmin     = $request->user()->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
        $currentRole = $this->resolveUserRole($user);

        $roles     = $this->buildRolesFor($request->user());
        $permGrid  = $this->permissionGrid($request->user(), $roles);
        $vendors   = Vendor::query()->orderBy('name')->get(['id', 'name']);
        $userPermissions = $user->effectivePermissionNames();
        $employees = Employee::query()
            ->where(function ($q) use ($user) {
                $q->whereDoesntHave('user')->orWhere('id', $user->employee_id);
            })
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return view('user::edit', compact('user', 'roles', 'permGrid', 'userPermissions', 'isAdmin', 'currentRole', 'vendors', 'employees'));
    }

    public function update(Request $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->guardRoleEscalation($request->user(), $request->input('system_role'));

        $data = UpdateUserData::validateAndCreate($request->all());
        $action->handle($user, $data);

        return redirect()->route('backend.users.index')
            ->with('success', 'Cập nhật tài khoản thành công.');
    }

    public function destroy(Request $request, User $user, DestroyUserAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $user);

        $name = $action->handle($user);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa tài khoản "' . $name . '".' ]);
        }

        return redirect()->route('backend.users.index')
            ->with('success', 'Đã xóa tài khoản "' . $name . '".');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function buildRolesFor(User $actor): array
    {
        $isAdmin = $actor->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);

        $excluded = $isAdmin ? [] : [RoleEnum::DIRECTOR->value, RoleEnum::ADMIN->value];

        return collect(RoleEnum::cases())
            ->reject(fn ($r) => in_array($r->value, $excluded, true))
            ->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()])
            ->values()
            ->all();
    }

    private function guardRoleEscalation(User $actor, ?string $requestedRole): void
    {
        if ($requestedRole === null) return;

        $restricted = [RoleEnum::DIRECTOR->value, RoleEnum::ADMIN->value];
        $isAdmin    = $actor->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);

        if (! $isAdmin && in_array($requestedRole, $restricted, true)) {
            abort(403, 'Bạn không có quyền gán vai trò này.');
        }
    }

    private function resolveUserRole(User $user): string
    {
        return $user->getRoleNames()->first() ?? '';
    }

    private function permissionGrid(User $actor, array $roles): array
    {
        $isAdmin = SyncUserPermissionsAction::isAdmin($actor);

        return [
            'columns' => collect(PermissionModule::ACTION_LABELS)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values()
                ->all(),
            'modules' => collect(PermissionModule::cases())
                ->map(fn (PermissionModule $m) => ['key' => $m->value, 'label' => $m->label(), 'actions' => $m->actions()])
                ->all(),
            'roleDefaults' => collect($roles)
                ->mapWithKeys(fn ($r) => [$r['value'] => SyncUserPermissionsAction::roleDefaults($r['value'])])
                ->all(),
            'grantable' => $isAdmin ? null : $actor->effectivePermissionNames(),
        ];
    }
}
