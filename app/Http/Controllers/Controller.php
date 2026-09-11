<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function response(bool $success, string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json(compact('success', 'message', 'data'), $status);
    }
}
