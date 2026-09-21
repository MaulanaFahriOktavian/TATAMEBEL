<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Authenticate user credentials, check active & workshop status, and issue a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        // Enforce safe authentication failure without leaking user existence or active state
        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
                'errors' => new \stdClass(),
            ], 401);
        }

        // Enforce workshop tenancy requirement
        if (! $user->workshop_id || ! $user->workshop) {
            return response()->json([
                'success' => false,
                'message' => 'User does not belong to a valid workshop.',
                'errors' => new \stdClass(),
            ], 403);
        }

        // Issue personal access token
        $token = $user->createToken('tatamebel-auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user->load('workshop')),
            ],
        ], 200);
    }

    /**
     * Revoke the current access token of the authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
            'data' => null,
        ], 200);
    }

    /**
     * Retrieve the profile, role, and workshop tenant data of the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user.',
            'data' => [
                'user' => new UserResource($request->user()->load('workshop')),
            ],
        ], 200);
    }
}
