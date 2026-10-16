<?php

namespace App\Repositories;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Interfaces\AuthRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthRepository implements AuthRepositoryInterface
{
    public function register(RegisterRequest $request)
    {
        return DB::transaction(fn () => User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]));
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        if (config('auth.require_email_verification', false) && ! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Please verify your email address before logging in.'],
            ]);
        }

        return [
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user,
        ];
    }

    public function logout($request)
    {
        $request->user()->currentAccessToken()->delete();
    }

    public function changePassword(array $data)
    {
        $user = auth()->user();

        if (! Hash::check($data['old_password'], $user->password)) {

            throw ValidationException::withMessages([
                'old_password' => ['Old password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return true;
    }

    public function forgotPassword(array $data)
    {
        Password::sendResetLink([
            'email' => $data['email'],
        ]);

        return true;
    }

    public function resetPassword(array $data)
    {
        $status = Password::reset(
            $data,
            function ($user, $password): void {
                DB::transaction(function () use ($user, $password): void {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->save();
                    $user->tokens()->delete();
                });
            }
        );

        if ($status !== Password::PASSWORD_RESET) {

            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);

        }

        return true;
    }
}
