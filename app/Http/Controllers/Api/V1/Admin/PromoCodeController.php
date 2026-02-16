<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\BaseController;
use App\Services\Admin\PromoCodeService;
use App\Http\Resources\PromoCodeResource;
use App\Http\Requests\Admin\StorePromoCodeRequest;


class PromoCodeController extends BaseController
{
     public function __construct(
        protected PromoCodeService $promoCodeService
    ) {
    }

    /**
     * Display a listing of promo codes
     */
    public function index(Request $request): JsonResponse
    {
        $promoCodes = $this->promoCodeService->getAllPromoCodes(
            filters: $request->all(),
            sortBy: $request->input('sort_by', 'created_at'),
            sortOrder: $request->input('sort_order', 'desc'),
            perPage: $request->input('per_page', 15)
        );

        return response()->json([
            'data' => PromoCodeResource::collection($promoCodes->items()),
            'meta' => [
                'total' => $promoCodes->total(),
                'per_page' => $promoCodes->perPage(),
                'current_page' => $promoCodes->currentPage(),
                'last_page' => $promoCodes->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created promo code
     */
    public function store(StorePromoCodeRequest $request): JsonResponse
    {
        $promoCodeData = PromoCodeDto::fromRequest($request->validated());
        $promoCode = $this->promoCodeService->createPromoCode($promoCodeData);

        return response()->json([
            'message' => 'Promo code created successfully',
            'data' => new PromoCodeResource($promoCode),
        ], 201);
    }

    /**
     * Display the specified promo code
     */
    public function show(int $id): JsonResponse
    {
        $promoCode = $this->promoCodeService->getPromoCodeById($id);

        if (!$promoCode) {
            return response()->json([
                'message' => 'Promo code not found',
            ], 404);
        }

        return response()->json([
            'data' => new PromoCodeResource($promoCode),
        ]);
    }

    /**
     * Update the specified promo code
     */
    public function update(StorePromoCodeRequest $request, int $id): JsonResponse
    {
        $promoCodeData = PromoCodeDto::fromRequest($request->validated());
        $updated = $this->promoCodeService->updatePromoCode($id, $promoCodeData);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update promo code',
            ], 500);
        }

        $promoCode = $this->promoCodeService->getPromoCodeById($id);

        return response()->json([
            'message' => 'Promo code updated successfully',
            'data' => new PromoCodeResource($promoCode),
        ]);
    }

    /**
     * Remove the specified promo code
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->promoCodeService->deletePromoCode($id);

        if (!$deleted) {
            return response()->json([
                'message' => 'Failed to delete promo code',
            ], 500);
        }

        return response()->json([
            'message' => 'Promo code deleted successfully',
        ]);
    }

    /**
     * Toggle promo code status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $updated = $this->promoCodeService->togglePromoCodeStatus($id);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update promo code status',
            ], 500);
        }

        return response()->json([
            'message' => 'Promo code status updated successfully',
        ]);
    }

    /**
     * Get promo code statistics
     */
    public function statistics(int $id): JsonResponse
    {
        $stats = $this->promoCodeService->getPromoCodeStatistics($id);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Generate unique promo code
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'prefix' => ['nullable', 'string', 'max:10'],
            'length' => ['nullable', 'integer', 'min:4', 'max:20'],
        ]);

        $code = $this->promoCodeService->generateUniqueCode(
            $request->input('prefix', ''),
            $request->input('length', 8)
        );

        return response()->json([
            'code' => $code,
        ]);
    }

    /**
     * Bulk create promo codes
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:100'],
            'prefix' => ['nullable', 'string', 'max:10'],
            'template' => ['required', 'array'],
        ]);

        $templateData = PromoCodeDto::fromRequest($request->template);
        
        $promoCodes = $this->promoCodeService->bulkCreatePromoCodes(
            $request->count,
            $templateData,
            $request->input('prefix', 'PROMO')
        );

        return response()->json([
            'message' => "{$request->count} promo codes created successfully",
            'data' => PromoCodeResource::collection($promoCodes),
        ], 201);
    }

    /**
     * Export promo codes
     */
    public function export(Request $request): JsonResponse
    {
        $csv = $this->promoCodeService->exportPromoCodes($request->all());

        return response()->json([
            'data' => $csv,
        ]);
    }

    /**
     * Get active promo codes
     */
    public function active(): JsonResponse
    {
        $promoCodes = $this->promoCodeService->getActivePromoCodes();

        return response()->json([
            'data' => PromoCodeResource::collection($promoCodes),
        ]);
    }

    /**
     * Get expired promo codes
     */
    public function expired(): JsonResponse
    {
        $promoCodes = $this->promoCodeService->getExpiredPromoCodes();

        return response()->json([
            'data' => PromoCodeResource::collection($promoCodes),
        ]);
    }
}

