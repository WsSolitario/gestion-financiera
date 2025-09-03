<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AppModeController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'mode_app' => config('app.mode_app'),
        ]);
    }
}

