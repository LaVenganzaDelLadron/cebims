<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBorrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['barangay' => ['required', 'string', 'max:150'], 'purpose' => ['required', 'string'], 'borrow_date' => ['required', 'date', 'after_or_equal:today'], 'expected_return_date' => ['required', 'date', 'after_or_equal:borrow_date'], 'items' => ['required', 'array', 'min:1'], 'items.*.equipment_id' => ['required', 'integer', Rule::exists('equipment', 'id')], 'items.*.quantity' => ['required', 'integer', 'min:1'], 'items.*.remarks' => ['nullable', 'string']];
    }

    public function messages(): array
    {
        return ['items.required' => 'At least one equipment item is required.'];
    }
}
