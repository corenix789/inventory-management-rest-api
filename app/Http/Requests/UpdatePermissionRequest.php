<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('permission');

        return [
            'name'         => "sometimes|required|string|max:100|unique:permissions,name,{$id}",
            'slug'         => "sometimes|nullable|string|max:100|unique:permissions,slug,{$id}",
            'display_name' => 'sometimes|nullable|string|max:100',
            'module'       => 'sometimes|nullable|string|max:50',
            'description'  => 'sometimes|nullable|string|max:500',
            'status'       => 'sometimes|nullable|boolean',
        ];
    }
}
