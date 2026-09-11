<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return ['items' => ['required', 'array', 'min:1'], 'items.*.equipment_id' => ['required', 'integer', Rule::exists('equipment', 'id')], 'items.*.quantity_returned' => ['required', 'integer', 'min:1'], 'items.*.item_condition' => ['required', Rule::in(['Excellent', 'Good', 'Damaged', 'Lost'])], 'items.*.remarks' => ['nullable', 'string'], 'remarks' => ['nullable', 'string']];
    }
}
