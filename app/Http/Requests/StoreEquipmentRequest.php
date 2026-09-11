<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return ['category_id' => ['required', 'integer', Rule::exists('categories', 'id')], 'equipment_name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string'], 'total_quantity' => ['required', 'integer', 'min:0'], 'available_quantity' => ['sometimes', 'integer', 'min:0', 'lte:total_quantity'], 'equipment_condition' => ['sometimes', Rule::in(['Excellent', 'Good', 'Fair', 'Needs Repair'])], 'storage_location' => ['nullable', 'string', 'max:150'], 'image' => ['nullable', 'string', 'max:255'], 'status' => ['sometimes', Rule::in(['Available', 'Unavailable', 'Maintenance'])]];
    }
}
