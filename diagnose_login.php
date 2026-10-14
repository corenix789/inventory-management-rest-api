<?php
define('LARAVEL_START', microtime(true));
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

echo "=== LOGIN DIAGNOSTIC ===" . PHP_EOL;
echo PHP_EOL;

// 1. User check
$user = User::where('email', 'bc250401991nah@vu.edu.pk')->first();
echo "1. User found: " . ($user ? "YES" : "NO") . PHP_EOL;
if ($user) {
    echo "   email_verified_at: " . ($user->email_verified_at ? $user->email_verified_at : "NULL") . PHP_EOL;
    echo "   password hash valid (test 'secret'): " . (Hash::check('secret', $user->password) ? "YES" : "NO") . PHP_EOL;
}
echo PHP_EOL;

// 2. Roles check
echo "2. User roles count: " . $user->roles()->count() . PHP_EOL;
echo "   Viewer role exists active: " . (Role::active()->where('slug', 'viewer')->exists() ? "YES" : "NO") . PHP_EOL;
echo PHP_EOL;

// 3. Attempt role assignment
echo "3. Testing role assignment..." . PHP_EOL;
if ($user && !$user->roles()->exists()) {
    $viewerRole = Role::active()->where('slug', 'viewer')->first();
    if ($viewerRole) {
        echo "   Viewer role id: " . $viewerRole->id . PHP_EOL;
        try {
            $user->assignRole($viewerRole);
            $freshUser = $user->fresh();
            echo "   Assigned OK. New roles count: " . $freshUser->roles()->count() . PHP_EOL;
            echo "   Roles: " . implode(', ', $freshUser->roles->pluck('slug')->toArray()) . PHP_EOL;
        } catch (Exception $e) {
            echo "   Assignment FAILED: " . $e->getMessage() . PHP_EOL;
        }
    } else {
        echo "   Viewer role NOT found!" . PHP_EOL;
    }
} else {
    echo "   User already has roles: " . implode(', ', $user->roles->pluck('slug')->toArray()) . PHP_EOL;
}
echo PHP_EOL;

// 4. Test permission check
$freshUser = $user->fresh();
echo "4. Permissions after assignment:" . PHP_EOL;
echo "   dashboard.view: " . ($freshUser->hasPermission('dashboard.view') ? "YES" : "NO") . PHP_EOL;
echo "   products.view: " . ($freshUser->hasPermission('products.view') ? "YES" : "NO") . PHP_EOL;
echo PHP_EOL;

// 5. Test Sanctum token creation
echo "5. Token creation test..." . PHP_EOL;
try {
    $token = $freshUser->createToken('diagnose_token')->plainTextToken;
    echo "   Token created: " . (strlen($token) > 0 ? "YES (" . strlen($token) . " chars)" : "NO") . PHP_EOL;
    
    // Test token lookup
    $accessToken = PersonalAccessToken::findToken($token);
    $resolvedUser = $accessToken?->tokenable;
    echo "   Token lookup: " . ($resolvedUser instanceof User ? "OK (user: " . $resolvedUser->email . ")" : "FAILED") . PHP_EOL;
    
    // Cleanup
    $accessToken->delete();
    echo "   Cleanup: OK" . PHP_EOL;
} catch (Exception $e) {
    echo "   Token error: " . $e->getMessage() . PHP_EOL;
}
echo PHP_EOL;

// 6. Session store check
echo "6. Session/DB checks:" . PHP_EOL;
echo "   sessions table exists: " . (Schema::hasTable('sessions') ? "YES" : "NO") . PHP_EOL;
echo "   CACHE_STORE: " . config('cache.default') . PHP_EOL;
echo "   SESSION_DRIVER: " . config('session.driver') . PHP_EOL;
echo PHP_EOL;

// 7. Auth config
echo "7. Auth config:" . PHP_EOL;
echo "   require_email_verification: " . (config('auth.require_email_verification', false) ? "TRUE" : "FALSE") . PHP_EOL;
echo "   APP_DEBUG: " . (config('app.debug') ? "TRUE" : "FALSE") . PHP_EOL;
echo PHP_EOL;

echo "=== DIAGNOSTIC COMPLETE ===" . PHP_EOL;
