<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'address' => ['required', 'string'], 'phone' => ['required', 'string', 'max:20'], 'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')], 'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')], 'password' => ['required', 'string', 'min:8', 'confirmed']];
    }

    public function messages(): array
    {
        return ['password.confirmed' => 'Password confirmation does not match.'];
    }
}
