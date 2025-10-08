<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/auth
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * GET /api/auth/create
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Registration endpoint - POST to /api/auth to register',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/auth (Register)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Display the specified resource.
     * GET /api/auth/{user}
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * GET /api/auth/{user}/edit
     */
    public function edit(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
            'message' => 'Update endpoint - PUT/PATCH to /api/auth to update profile',
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/auth
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        assert($user instanceof User);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        $updateData = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'user' => $user->fresh(),
            'message' => 'Profile updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/auth (Logout)
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        assert($user instanceof User);

        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Login and create API token
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Revoke all tokens for user
     * POST /api/auth/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        assert($user instanceof User);

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out from all devices',
        ]);
    }
}
