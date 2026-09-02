<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'display_name',
        'module',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    /**
     * The roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')
                    ->withTimestamps();
    }

    // ─────────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Permission $permission) {
            if (empty($permission->slug)) {
                $permission->slug = \Illuminate\Support\Str::slug($permission->name);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
