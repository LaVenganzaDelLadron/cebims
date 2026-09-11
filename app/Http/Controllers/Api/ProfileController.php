<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth()->user();
        $this->authorize('view', $user);

        return $this->response(true, 'Profile retrieved.', $user);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);
        $user->update($request->validated());

        return $this->response(true, 'Profile updated.', $user->fresh());
    }
}
