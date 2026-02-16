<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use Hash;
use Exception;
use App\Dto\AddressDto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\AddressResource;
use App\Http\Controllers\BaseController;
use App\Http\Resources\CustomerResource;
use App\Http\Requests\Frontend\AddAddressRequest;
use App\Http\Requests\Frontend\UpdateProfileRequest;


class ProfileController extends BaseController
{
    /**
     * Display the customer's profile
     */
    public function show(Request $request): JsonResponse
    {
        $customer = $request->user()->load(['addresses', 'wishlist']);

        return response()->json([
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Update the customer's profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $customer = $request->user();
            $customer->update($request->validated());

            return response()->json([
                'message' => 'Profile updated successfully',
                'data' => new CustomerResource($customer->fresh()),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get customer's addresses
     */
    public function addresses(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses;

        return response()->json([
            'data' => AddressResource::collection($addresses),
        ]);
    }

    /**
     * Store a new address
     */
    public function storeAddress(AddAddressRequest $request): JsonResponse
    {
        try {
            $addressData = AddressDto::fromRequest(
                $request->validated(),
                $request->user()->id
            );

            $address = $request->user()->addresses()->create($addressData->toArray());

            if ($request->is_default || $request->user()->addresses()->count() === 1) {
                $address->makeDefault();
            }

            return response()->json([
                'message' => 'Address added successfully',
                'data' => new AddressResource($address),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified address
     */
    public function showAddress(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->find($id);

        if (!$address) {
            return response()->json([
                'message' => 'Address not found',
            ], 404);
        }

        return response()->json([
            'data' => new AddressResource($address),
        ]);
    }

    /**
     * Update the specified address
     */
    public function updateAddress(AddAddressRequest $request, int $id): JsonResponse
    {
        try {
            $address = $request->user()->addresses()->find($id);

            if (!$address) {
                return response()->json([
                    'message' => 'Address not found',
                ], 404);
            }

            $addressData = AddressDto::fromRequest(
                $request->validated(),
                $request->user()->id
            );

            $address->update($addressData->toArray());

            if ($request->is_default) {
                $address->makeDefault();
            }

            return response()->json([
                'message' => 'Address updated successfully',
                'data' => new AddressResource($address->fresh()),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified address
     */
    public function deleteAddress(Request $request, int $id): JsonResponse
    {
        try {
            $address = $request->user()->addresses()->find($id);

            if (!$address) {
                return response()->json([
                    'message' => 'Address not found',
                ], 404);
            }

            $address->delete();

            return response()->json([
                'message' => 'Address deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Set default address
     */
    public function setDefaultAddress(Request $request, int $id): JsonResponse
    {
        try {
            $address = $request->user()->addresses()->find($id);

            if (!$address) {
                return response()->json([
                    'message' => 'Address not found',
                ], 404);
            }

            $address->makeDefault();

            return response()->json([
                'message' => 'Default address updated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $customer = $request->user();

            if (!Hash::check($request->current_password, $customer->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect',
                ], 400);
            }

            $customer->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'message' => 'Password changed successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

