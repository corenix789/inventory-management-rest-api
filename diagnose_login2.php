<?php
define('LARAVEL_START', microtime(true));
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "=== ROLE ASSIGNMENT TYPE DIAGNOSIS ===" . PHP_EOL;

$user = User::where('email', 'bc250401991nah@vu.edu.pk')->first();
$viewerRole = Role::active()->where('slug', 'viewer')->first();

echo "viewerRole type: " . get_class($viewerRole) . PHP_EOL;
echo "viewerRole id: " . $viewerRole->id . PHP_EOL;
echo PHP_EOL;

// Method 1: pass a Role object - this is what handleLogin() does
echo "TEST 1: assignRole(Role object)" . PHP_EOL;
try {
    $user->assignRole($viewerRole);
    echo "  Result: SUCCESS" . PHP_EOL;
} catch (\TypeError $e) {
    echo "  TypeError: " . $e->getMessage() . PHP_EOL;
} catch (\Exception $e) {
    echo "  Exception [" . get_class($e) . "]: " . $e->getMessage() . PHP_EOL;
} catch (\Throwable $e) {
    echo "  Throwable [" . get_class($e) . "]: " . $e->getMessage() . PHP_EOL;
}

// Make sure user has no roles still
echo "  Roles after: " . $user->fresh()->roles()->count() . PHP_EOL;
echo PHP_EOL;

// Method 2: pass a string slug
echo "TEST 2: assignRole('viewer') string" . PHP_EOL;
try {
    $user->assignRole('viewer');
    echo "  Result: SUCCESS" . PHP_EOL;
} catch (\Throwable $e) {
    echo "  Error [" . get_class($e) . "]: " . $e->getMessage() . PHP_EOL;
}
echo "  Roles after: " . $user->fresh()->roles()->count() . PHP_EOL;
echo PHP_EOL;

// Method 3: pass an integer ID
echo "TEST 3: assignRole(" . $viewerRole->id . ") integer" . PHP_EOL;
// Remove role first
$user->removeRole('viewer');
echo "  Roles before: " . $user->fresh()->roles()->count() . PHP_EOL;
try {
    $user->assignRole($viewerRole->id);
    echo "  Result: SUCCESS" . PHP_EOL;
} catch (\Throwable $e) {
    echo "  Error [" . get_class($e) . "]: " . $e->getMessage() . PHP_EOL;
}
echo "  Roles after: " . $user->fresh()->roles()->count() . PHP_EOL;
echo PHP_EOL;

// Method 4: direct syncWithoutDetaching (bypassing assignRole)
echo "TEST 4: direct syncWithoutDetaching([id])" . PHP_EOL;
$user->removeRole('viewer');
try {
    $user->roles()->syncWithoutDetaching([$viewerRole->id]);
    echo "  Result: SUCCESS" . PHP_EOL;
} catch (\Throwable $e) {
    echo "  Error [" . get_class($e) . "]: " . $e->getMessage() . PHP_EOL;
}
echo "  Roles after: " . $user->fresh()->roles()->count() . PHP_EOL;
echo PHP_EOL;

// Cleanup - remove the role so the test is clean
$user->removeRole('viewer');
echo "Final cleanup - roles: " . $user->fresh()->roles()->count() . PHP_EOL;
echo PHP_EOL;
echo "=== DONE ===" . PHP_EOL;
