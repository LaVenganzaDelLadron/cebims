<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $token = $user->createToken('api', $user->role === 'admin' ? ['*', 'admin'] : ['user'])->plainTextToken;

        return $this->response(true, 'Registration successful.', ['user' => $user, 'token' => $token], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('login'))->orWhere('username', $request->string('login'))->first();
        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            return $this->response(false, 'Invalid credentials.', null, 401);
        }

        return $this->response(true, 'Login successful.', ['user' => $user, 'token' => $user->createToken('api', $user->role === 'admin' ? ['*', 'admin'] : ['user'])->plainTextToken]);
    }

    public function me(): JsonResponse
    {
        return $this->response(true, 'User profile retrieved.', auth()->user());
    }

    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()?->delete();

        return $this->response(true, 'Logged out successfully.');
    }
}
