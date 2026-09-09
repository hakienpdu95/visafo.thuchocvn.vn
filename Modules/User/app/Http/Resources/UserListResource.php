<?php

namespace Modules\User\Http\Resources;

use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $spatiRole = $this->getRoleNames()->first();
        $roleEnum  = $spatiRole ? RoleEnum::tryFrom($spatiRole) : null;

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'department'        => $this->department,
            'avatar_url'        => 'https://api.dicebear.com/9.x/initials/svg?seed=' . urlencode($this->name)
                                    . '&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700',

            'role'              => $roleEnum?->value ?? $spatiRole,
            'role_label'        => $roleEnum?->label() ?? ($spatiRole ?? '—'),
            'role_badge'        => $roleEnum?->badgeClass() ?? 'badge-ghost',

            'is_active'         => $this->is_active,
            'status_label'      => $this->is_active ? 'Hoạt động' : 'Vô hiệu',
            'status_badge'      => $this->is_active ? 'badge-success' : 'badge-ghost',

            'can_delete'        => $request->user()?->id !== $this->id,

            'created_at'        => $this->created_at?->format('d/m/Y'),

            'edit_url'          => route('backend.users.edit',    $this->resource),
            'delete_url'        => route('backend.users.destroy', $this->resource),
        ];
    }
}
