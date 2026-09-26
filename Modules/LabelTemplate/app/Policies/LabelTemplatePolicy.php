<?php

namespace Modules\LabelTemplate\Policies;

use App\Models\User;
use App\Support\Permissions\ModuleAccess;
use Modules\LabelTemplate\Models\LabelTemplate;

class LabelTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return ModuleAccess::viewAny($user, 'label_template');
    }

    public function view(User $user, LabelTemplate $labelTemplate): bool
    {
        return ModuleAccess::view($user, 'label_template', $labelTemplate);
    }

    public function create(User $user): bool
    {
        return ModuleAccess::create($user, 'label_template');
    }

    public function update(User $user, LabelTemplate $labelTemplate): bool
    {
        return ModuleAccess::update($user, 'label_template', $labelTemplate);
    }

    public function delete(User $user, LabelTemplate $labelTemplate): bool
    {
        return ModuleAccess::delete($user, 'label_template', $labelTemplate);
    }
}
