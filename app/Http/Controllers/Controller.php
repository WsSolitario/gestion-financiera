<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function ok(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    protected function created(array $data = []): JsonResponse
    {
        return response()->json($data, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function fail(string $message, int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['message' => $message], $extra), $status);
    }
}
