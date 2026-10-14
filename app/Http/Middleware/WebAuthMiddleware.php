<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class WebAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Redirect to login if no session token is present.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = session('auth_token');
        $accessToken = $token ? PersonalAccessToken::findToken($token) : null;
        $user = $accessToken?->tokenable;

        if (! $user instanceof User) {
            if ($token) {
                $request->session()->forget(['auth_token', 'auth_user']);
            }

            return redirect()->route('dashboard.login')
                ->with('error', 'Please log in to access the dashboard.');
        }

        $expiration = config('sanctum.expiration');
        if ($accessToken->expires_at?->isPast() || ($expiration && $accessToken->created_at?->addMinutes($expiration)->isPast())) {
            $accessToken->delete();
            $request->session()->forget(['auth_token', 'auth_user']);

            return redirect()->route('dashboard.login')
                ->with('error', 'Your session has expired. Please log in again.');
        }

        $request->setUserResolver(fn () => $user);
        Auth::setUser($user);
        $request->session()->put('auth_user', $user->toArray());

        return $next($request);
    }
}
