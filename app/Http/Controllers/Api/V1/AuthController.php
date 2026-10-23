<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // Same message whether the email or the password is wrong,
        // so the endpoint can't be used to discover registered emails.
        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken($request->validated('device_name') ?? 'api');

        return response()->json([
            'token' => $token->plainTextToken, // shown once, only the hash is stored
            'token_type' => 'Bearer',
            'user' => UserResource::make($user),
        ]);
    }

    public function logout(Request $request): Response
    {
        // Revoke only the token used for this request; other devices stay logged in.
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
