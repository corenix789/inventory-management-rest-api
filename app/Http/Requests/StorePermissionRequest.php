<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:100|unique:permissions,name',
            'slug'         => 'nullable|string|max:100|unique:permissions,slug',
            'display_name' => 'nullable|string|max:100',
            'module'       => 'nullable|string|max:50',
            'description'  => 'nullable|string|max:500',
            'status'       => 'nullable|boolean',
        ];
    }
}
