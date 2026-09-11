<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return ['category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')], 'equipment_name' => ['sometimes', 'string', 'max:150'], 'description' => ['nullable', 'string'], 'total_quantity' => ['sometimes', 'integer', 'min:1'], 'available_quantity' => ['sometimes', 'integer', 'min:0', 'lte:total_quantity'], 'equipment_condition' => ['sometimes', Rule::in(['Excellent', 'Good', 'Fair', 'Needs Repair'])], 'storage_location' => ['nullable', 'string', 'max:150'], 'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'], 'status' => ['sometimes', Rule::in(['Available', 'Unavailable', 'Maintenance'])]];
    }
}
