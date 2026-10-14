<?php
/**
 * Post-fix verification script for the login flow.
 * Simulates exactly what handleLogin() does.
 */
define('LARAVEL_START', microtime(true));
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

echo "=== LOGIN FIX VERIFICATION ===" . PHP_EOL . PHP_EOL;

$userEmail = 'bc250401991nah@vu.edu.pk';
$pass = null; // unknown — just testing the flow

// --- Step 1: Find user ---
$user = User::where('email', $userEmail)->first();
echo "Step 1 - User lookup: " . ($user ? "PASS (id={$user->id})" : "FAIL (user not found)") . PHP_EOL;

// --- Step 2: Check password (skip actual password since we don't know it) ---
echo "Step 2 - Password check: SKIP (cannot verify without the actual password)" . PHP_EOL;

// --- Step 3: Email verification ---
$requireVerification = config('auth.require_email_verification', false);
echo "Step 3 - Email verification required: " . ($requireVerification ? "YES" : "NO (skip)") . PHP_EOL;

// --- Step 4: Role assignment ---
echo "Step 4 - Role assignment..." . PHP_EOL;
$hadRoleBefore = $user->roles()->exists();
echo "   Had role before: " . ($hadRoleBefore ? "YES" : "NO") . PHP_EOL;

if (! $user->roles()->exists()) {
    $viewerRole = Role::active()->where('slug', 'viewer')->first();
    if (! $viewerRole) {
        echo "   FAIL: Viewer role not found. Run: php artisan db:seed --class=RbacSeeder" . PHP_EOL;
        exit(1);
    }
    try {
        // This is what the FIXED handleLogin() now does — pass the slug string
        $user->assignRole('viewer');
        echo "   Viewer role assigned: PASS" . PHP_EOL;
    } catch (\Throwable $e) {
        echo "   Role assignment FAILED: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
} else {
    echo "   User already has roles: " . implode(', ', $user->roles->pluck('slug')->toArray()) . PHP_EOL;
}

// --- Step 5: Verify permission check ---
$freshUser = $user->fresh();
$hasDashboardView = $freshUser->hasPermission('dashboard.view');
echo "Step 5 - dashboard.view permission: " . ($hasDashboardView ? "PASS" : "FAIL") . PHP_EOL;

// --- Step 6: Sanctum token creation ---
echo "Step 6 - Token creation..." . PHP_EOL;
try {
    $token = $freshUser->createToken('verify_login')->plainTextToken;
    $accessToken = PersonalAccessToken::findToken($token);
    $resolvedUser = $accessToken?->tokenable;
    
    echo "   Token created: " . (strlen($token) > 0 ? "PASS" : "FAIL") . PHP_EOL;
    echo "   Token lookup resolves user: " . ($resolvedUser instanceof User ? "PASS ({$resolvedUser->email})" : "FAIL") . PHP_EOL;
    
    // Cleanup test token
    $accessToken->delete();
    echo "   Token cleanup: OK" . PHP_EOL;
} catch (\Throwable $e) {
    echo "   Token FAILED: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// --- Step 7: Verify role assignment type fix ---
echo "Step 7 - assignRole type fix verification..." . PHP_EOL;
// Remove viewer role first, then test passing Role object
$freshUser->roles()->detach();
$viewerRole = Role::active()->where('slug', 'viewer')->first();

try {
    // Test: pass Role object (the OLD bug path — should now work with the fix)
    $freshUser->assignRole($viewerRole);
    echo "   assignRole(Role object): PASS" . PHP_EOL;
} catch (\Throwable $e) {
    echo "   assignRole(Role object): FAIL — " . $e->getMessage() . PHP_EOL;
}

// Verify the permission is back
$finalUser = $freshUser->fresh();
echo "   dashboard.view after re-assignment: " . ($finalUser->hasPermission('dashboard.view') ? "PASS" : "FAIL") . PHP_EOL;

echo PHP_EOL;
echo "=== ALL CHECKS COMPLETE ===" . PHP_EOL;
echo PHP_EOL;

$rolesNow = $finalUser->roles()->count();
echo "User '" . $userEmail . "' role status: " . $rolesNow . " role(s) assigned." . PHP_EOL;
if ($rolesNow > 0) {
    echo "Roles: " . implode(', ', $finalUser->roles->pluck('slug')->toArray()) . PHP_EOL;
}
