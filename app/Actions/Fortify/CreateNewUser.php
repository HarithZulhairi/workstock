<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\Hash;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'position' => ['required', 'string', 'max:50'],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'full_name' => $input['full_name'],
            'email' => $input['email'],
            'phone_number' => $input['phone_number'],
            'address' => $input['address'],
            'position' => $input['position'],
            'password' => Hash::make($input['password']),
            'is_active' => true,
        ]);
    }
}
