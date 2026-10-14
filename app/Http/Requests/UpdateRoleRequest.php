<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('role');

        return [
            'name' => "sometimes|required|string|max:100|unique:roles,name,{$id}",
            'slug' => "sometimes|nullable|string|max:100|unique:roles,slug,{$id}",
            'display_name' => 'sometimes|nullable|string|max:100',
            'description' => 'sometimes|nullable|string|max:500',
            'color' => 'sometimes|nullable|string|max:20',
            'status' => 'sometimes|boolean',
            'permissions' => 'sometimes|nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ];
    }
}
