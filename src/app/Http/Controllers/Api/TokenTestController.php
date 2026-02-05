<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenTestController extends Controller
{
    /**
     * Test endpoint to verify API token authentication
     *
     * Returns basic information about the authenticated user and their token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();

        return response()->json([
            'message' => 'Token is valid',
            'user' => [
                'uername' => $user->name,
            ],
            'token' => [
                'name' => $token?->name,
                'created_at' => $token?->created_at?->toIso8601String(),
                'expires_at' => $token?->expires_at?->toIso8601String(),
                'last_used_at' => $token?->last_used_at?->toIso8601String(),
            ],
            'request_time' => now()->toIso8601String(),
        ]);
    }
}
