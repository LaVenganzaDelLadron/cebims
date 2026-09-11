<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return ['category_name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string']];
    }

    public function messages(): array
    {
        return ['category_name.required' => 'A category name is required.'];
    }
}
