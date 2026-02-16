<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Dto\CustomerDto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\OrderResource;
use App\Http\Controllers\BaseController;
use App\Http\Resources\CustomerResource;
use App\Services\Admin\AdminCustomerService;
use App\Http\Requests\Admin\StoreCustomerRequest;


class CustomerController extends BaseController
{
    public function __construct(
        protected AdminCustomerService $customerService
    ) {
    }

    /**
     * Display a listing of customers
     */
    public function index(Request $request): JsonResponse
    {
        $customers = $this->customerService->getAllCustomers(
            filters: $request->all(),
            sortBy: $request->input('sort_by', 'created_at'),
            sortOrder: $request->input('sort_order', 'desc'),
            perPage: $request->input('per_page', 15)
        );

        return response()->json([
            'data' => CustomerResource::collection($customers->items()),
            'meta' => [
                'total' => $customers->total(),
                'per_page' => $customers->perPage(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created customer
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customerData = CustomerDto::fromRequest($request->validated());
        $customer = $this->customerService->createCustomer($customerData);

        return response()->json([
            'message' => 'Customer created successfully',
            'data' => new CustomerResource($customer),
        ], 201);
    }

    /**
     * Display the specified customer
     */
    public function show(int $id): JsonResponse
    {
        $customer = $this->customerService->getCustomerById($id);

        if (!$customer) {
            return response()->json([
                'message' => 'Customer not found',
            ], 404);
        }

        return response()->json([
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Update customer status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:active,inactive,blocked'],
        ]);

        $updated = $this->customerService->updateCustomerStatus($id, $request->status);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update customer status',
            ], 500);
        }

        return response()->json([
            'message' => 'Customer status updated successfully',
        ]);
    }

    /**
     * Block customer
     */
    public function block(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $updated = $this->customerService->blockCustomer($id, $request->reason);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to block customer',
            ], 500);
        }

        return response()->json([
            'message' => 'Customer blocked successfully',
        ]);
    }

    /**
     * Unblock customer
     */
    public function unblock(int $id): JsonResponse
    {
        $updated = $this->customerService->unblockCustomer($id);

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to unblock customer',
            ], 500);
        }

        return response()->json([
            'message' => 'Customer unblocked successfully',
        ]);
    }

    /**
     * Get customer statistics
     */
    public function statistics(int $id): JsonResponse
    {
        $stats = $this->customerService->getCustomerStatistics($id);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get customer orders
     */
    public function orders(int $id, Request $request): JsonResponse
    {
        $orders = $this->customerService->getCustomerOrders(
            $id,
            $request->input('per_page', 15)
        );

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Search customers
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2'],
        ]);

        $customers = $this->customerService->searchCustomers(
            $request->query,
            $request->input('per_page', 15)
        );

        return response()->json([
            'data' => CustomerResource::collection($customers->items()),
            'meta' => [
                'total' => $customers->total(),
                'per_page' => $customers->perPage(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
            ],
        ]);
    }

    /**
     * Get top customers
     */
    public function topCustomers(Request $request): JsonResponse
    {
        $customers = $this->customerService->getTopCustomers(
            $request->input('limit', 10)
        );

        return response()->json([
            'data' => CustomerResource::collection($customers),
        ]);
    }

    /**
     * Export customers
     */
    public function export(Request $request): JsonResponse
    {
        $csv = $this->customerService->exportCustomers($request->all());

        return response()->json([
            'data' => $csv,
        ]);
    }

    /**
     * Get customer activity log
     */
    public function activity(int $id, Request $request): JsonResponse
    {
        $activities = $this->customerService->getCustomerActivity(
            $id,
            $request->input('limit', 20)
        );

        return response()->json([
            'data' => $activities,
        ]);
    }
}

