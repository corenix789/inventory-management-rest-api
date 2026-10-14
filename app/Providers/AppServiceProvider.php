<?php

namespace App\Providers;

use App\Interfaces\AuthRepositoryInterface;
use App\Interfaces\BrandRepositoryInterface;
use App\Interfaces\CategoryRepositoryInterface;
use App\Interfaces\PermissionRepositoryInterface;
use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\RoleRepositoryInterface;
use App\Interfaces\TaxRepositoryInterface;
use App\Interfaces\UnitRepositoryInterface;
use App\Interfaces\WarehouseRepositoryInterface;
use App\Models\User;
use App\Repositories\AuthRepository;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Repositories\CustomerRepository;
use App\Repositories\Eloquent\SupplierRepository;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Repositories\Interfaces\SupplierRepositoryInterface;
use App\Repositories\PermissionRepository;
use App\Repositories\ProductRepository;
use App\Repositories\PurchaseRepository;
// Roles & Permissions
use App\Repositories\RoleRepository;
use App\Repositories\TaxRepository;
use App\Repositories\UnitRepository;
use App\Repositories\WarehouseRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any Application services.
     */
    public function register(): void
    {
        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class
        );

        $this->app->bind(
            AuthRepositoryInterface::class,
            AuthRepository::class
        );

        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );
        $this->app->bind(
            BrandRepositoryInterface::class,
            BrandRepository::class
        );
        $this->app->bind(
            UnitRepositoryInterface::class,
            UnitRepository::class
        );
        $this->app->bind(
            TaxRepositoryInterface::class,
            TaxRepository::class
        );
        $this->app->bind(
            WarehouseRepositoryInterface::class,
            WarehouseRepository::class
        );
        $this->app->bind(
            SupplierRepositoryInterface::class,
            SupplierRepository::class
        );
        $this->app->bind(
            CustomerRepositoryInterface::class,
            CustomerRepository::class
        );
        $this->app->bind(
            PurchaseRepositoryInterface::class,
            PurchaseRepository::class
        );

        // ── Roles & Permissions ───────────────────────────────────
        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );
        $this->app->bind(
            PermissionRepositoryInterface::class,
            PermissionRepository::class
        );
    }

    /**
     * Bootstrap any Application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return str_contains($ability, '.') ? $user->hasPermission($ability) : null;
        });
    }
}
