<?php

namespace Modules\User\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class DestroyUserAction
{
    use AsAction;

    public function handle(User $user): string
    {
        $name = $user->name;
        $user->delete();

        return $name;
    }
}
