<?php

namespace App\Http\Controllers\Api\V1\Frontend\Auth;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\BaseController;
use App\Http\Resources\CustomerResource;
use App\Http\Requests\Frontend\LoginRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Frontend\RegisterRequest;


class AuthController extends BaseController
{
    /**
     * Register new customer
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $customer = Customer::create([
            ...$request->validated(),
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        $token = $customer->createToken('customer-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'customer' => new CustomerResource($customer),
            'token' => $token,
        ], 201);
    }

    /**
     * Login customer
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $customer = Customer::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$customer->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        $customer->updateLastLogin();
        $token = $customer->createToken('customer-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'customer' => new CustomerResource($customer),
            'token' => $token,
        ]);
    }

    /**
     * Logout customer
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated customer
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new CustomerResource($request->user()->load(['addresses', 'wishlist'])),
        ]);
    }
}


