<?php

use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\PurchaseDetailController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TaxController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\UserRoleController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:3,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
            ->middleware('throttle:3,1');
        Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:3,1');

        Route::apiResource('categories', CategoryController::class)->middleware('permission:categories');
        Route::apiResource('products', ProductController::class)->middleware('permission:products');
        Route::apiResource('brands', BrandController::class)->middleware('permission:brands');
        Route::apiResource('units', UnitController::class)->middleware('permission:units');
        Route::apiResource('taxes', TaxController::class)->middleware('permission:taxes');
        Route::apiResource('warehouses', WarehouseController::class)->middleware('permission:warehouses');
        Route::post('warehouses/{id}/restore', [WarehouseController::class, 'restore'])
            ->middleware('permission:warehouses.edit');
        Route::delete('warehouses/{id}/force-delete', [WarehouseController::class, 'forceDelete'])
            ->middleware('permission:warehouses.delete');
        Route::apiResource('suppliers', SupplierController::class)->middleware('permission:suppliers');
        Route::apiResource('customers', CustomerController::class)->middleware('permission:customers');
        Route::apiResource('purchases', PurchaseController::class)->middleware('permission:purchases');
        Route::get('purchases/{purchase}/details', [PurchaseDetailController::class, 'index'])
            ->middleware(['permission:purchases.view', 'permission:purchase-details.view']);
        Route::get('purchases/{purchase}/details/{detail}', [PurchaseDetailController::class, 'show'])
            ->middleware(['permission:purchases.view-details', 'permission:purchase-details.view-details']);

        Route::get('permissions/grouped', [PermissionController::class, 'grouped'])
            ->middleware('permission:permissions.view');
        Route::apiResource('roles', RoleController::class)->middleware('permission:roles');
        Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->middleware('permission:roles.assign-permissions');
        Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])
            ->middleware('permission:roles.view');
        Route::apiResource('permissions', PermissionController::class)->middleware('permission:permissions');
        Route::get('users/{user}/roles', [UserRoleController::class, 'show'])
            ->middleware('permission:users.view-details');
        Route::put('users/{user}/roles', [UserRoleController::class, 'sync'])
            ->middleware('permission:users.assign-roles');
    });
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
});
