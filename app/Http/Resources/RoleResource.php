<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'display_name' => $this->display_name,
            'description'  => $this->description,
            'color'        => $this->color,
            'is_default'   => $this->is_default,
            'status'       => $this->status,
            'permissions'  => PermissionResource::collection($this->whenLoaded('permissions')),
            'users_count'  => $this->whenCounted('users'),
            'created_at'   => $this->created_at?->toDateTimeString(),
            'updated_at'   => $this->updated_at?->toDateTimeString(),
        ];
    }
}
