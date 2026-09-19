<?php

namespace Modules\LabelTemplate\Policies;

use App\Models\User;
use Modules\LabelTemplate\Models\LabelTemplate;

class LabelTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('label_template.view');
    }

    public function view(User $user, LabelTemplate $labelTemplate): bool
    {
        return $user->can('label_template.view');
    }

    public function create(User $user): bool
    {
        return $user->can('label_template.manage');
    }

    public function update(User $user, LabelTemplate $labelTemplate): bool
    {
        return $user->can('label_template.manage');
    }

    public function delete(User $user, LabelTemplate $labelTemplate): bool
    {
        return $user->can('label_template.manage');
    }
}
