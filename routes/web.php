<?php

use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Web\RoleManagementController;
use App\Http\Controllers\Web\WebDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root — API info response
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return response()->json([
        'message' => 'Inventory Management REST API',
        'version' => 'v1',
        'status' => 'Running Successfully',
        'dashboard' => url('/dashboard/login'),
    ]);
});

/*
|--------------------------------------------------------------------------
| Password Reset (Blade view)
|--------------------------------------------------------------------------
*/
Route::get('/reset-password', [ResetPasswordController::class, 'index'])
    ->name('password.reset');

/*
|--------------------------------------------------------------------------
| Dashboard Auth (public — no middleware)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/login', [WebDashboardController::class, 'showLogin'])->name('dashboard.login');
Route::post('/dashboard/login', [WebDashboardController::class, 'handleLogin'])->name('dashboard.login.post');
Route::get('/dashboard/register', [WebDashboardController::class, 'showRegister'])->name('dashboard.register');
Route::post('/dashboard/register', [WebDashboardController::class, 'handleRegister'])->name('dashboard.register.post');
Route::post('/dashboard/logout', [WebDashboardController::class, 'logout'])->name('dashboard.logout');
// Keep GET logout as fallback (link clicks)
Route::get('/dashboard/logout', [WebDashboardController::class, 'logout'])->name('dashboard.logout.get');

/*
|--------------------------------------------------------------------------
| Dashboard Pages (protected — require session token)
|--------------------------------------------------------------------------
*/
Route::middleware('web.auth')->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/home', [WebDashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('index');
    Route::get('/products', [WebDashboardController::class, 'products'])->middleware('permission:products.view')->name('products');
    Route::get('/categories', [WebDashboardController::class, 'categories'])->middleware('permission:categories.view')->name('categories');
    Route::get('/brands', [WebDashboardController::class, 'brands'])->middleware('permission:brands.view')->name('brands');
    Route::get('/units', [WebDashboardController::class, 'units'])->middleware('permission:units.view')->name('units');
    Route::get('/taxes', [WebDashboardController::class, 'taxes'])->middleware('permission:taxes.view')->name('taxes');
    Route::get('/warehouses', [WebDashboardController::class, 'warehouses'])->middleware('permission:warehouses.view')->name('warehouses');
    Route::get('/suppliers', [WebDashboardController::class, 'suppliers'])->middleware('permission:suppliers.view')->name('suppliers');
    Route::get('/customers', [WebDashboardController::class, 'customers'])->middleware('permission:customers.view')->name('customers');
    Route::get('/purchases', [WebDashboardController::class, 'purchases'])->middleware('permission:purchases.view')->name('purchases');

    Route::get('/roles', [RoleManagementController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::get('/roles/create', [RoleManagementController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
    Route::post('/roles', [RoleManagementController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
    Route::get('/roles/{role}', [RoleManagementController::class, 'show'])->middleware('permission:roles.view-details')->name('roles.show');
    Route::get('/roles/{role}/edit', [RoleManagementController::class, 'edit'])->middleware('permission:roles.edit')->name('roles.edit');
    Route::put('/roles/{role}', [RoleManagementController::class, 'update'])->middleware('permission:roles.edit')->name('roles.update');
    Route::delete('/roles/{role}', [RoleManagementController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

    Route::get('/users', [RoleManagementController::class, 'users'])->middleware('permission:users.view')->name('users.index');
    Route::put('/users/{user}/roles', [RoleManagementController::class, 'syncUserRoles'])
        ->middleware('permission:users.assign-roles')->name('users.roles.sync');
});

/*
|--------------------------------------------------------------------------
| /dashboard redirect → login if not authed, dashboard if authed
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    if (session('auth_token')) {
        return redirect()->route('dashboard.index');
    }

    return redirect()->route('dashboard.login');
})->name('dashboard.home');
