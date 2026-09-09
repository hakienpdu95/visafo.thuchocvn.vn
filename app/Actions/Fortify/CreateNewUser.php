<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Modules\Auth\Actions\RegisterOrganizationAction;
use Modules\Auth\Data\RegisterOrganizationData;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ], [
            'name.required' => 'Họ và tên là bắt buộc.',
            'email.unique'  => 'Email này đã được sử dụng.',
        ])->validate();

        return RegisterOrganizationAction::run(
            RegisterOrganizationData::from($input)
        );
    }
}
