<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([...$request->validated(), 'role' => 'user']);
        $token = $user->createToken('api', ['user'])->plainTextToken;

        return $this->response(true, 'Registration successful.', ['user' => $user, 'token' => $token], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('login'))->orWhere('username', $request->string('login'))->first();
        if (! $user || $user->isDisabled() || ! Hash::check($request->string('password'), $user->password)) {
            Log::warning('Failed login attempt', ['identifier' => Str::lower((string) $request->input('login')), 'ip' => $request->ip()]);

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

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $status = Password::sendResetLink($request->only('email'));

        return $this->response($status === Password::RESET_LINK_SENT, __($status), null, $status === Password::RESET_LINK_SENT ? 200 : 422);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', 'min:8']]);
        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill(['password' => $password])->save();
            $user->tokens()->delete();
        });

        return $this->response($status === Password::PASSWORD_RESET, __($status), null, $status === Password::PASSWORD_RESET ? 200 : 422);
    }

    public function verificationNotice(): JsonResponse
    {
        return $this->response(true, 'Verification link sent.');
    }

    public function sendVerification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->response(true, 'Email already verified.');
        }
        $request->user()->sendEmailVerificationNotification();

        return $this->response(true, 'Verification link sent.');
    }

    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403);
        $user->markEmailAsVerified();

        return $this->response(true, 'Email verified.');
    }
}
