<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\BaseController;
use App\Services\Admin\AnalyticsService;


class DashboardController extends BaseController
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {
    }

    /**
     * Get dashboard overview
     */
    public function overview(Request $request): JsonResponse
    {
        $data = $this->analyticsService->getDashboardOverview($request->all());

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get revenue chart data
     */
    public function revenueChart(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'interval' => ['nullable', 'in:hourly,daily,weekly,monthly,yearly'],
        ]);

        $data = $this->analyticsService->getRevenueChart(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date),
            $request->input('interval', 'daily')
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get orders chart data
     */
    public function ordersChart(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'interval' => ['nullable', 'in:hourly,daily,weekly,monthly,yearly'],
        ]);

        $data = $this->analyticsService->getOrdersChart(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date),
            $request->input('interval', 'daily')
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get best selling products
     */
    public function bestSellingProducts(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $data = $this->analyticsService->getBestSellingProducts(
            $request->input('limit', 10),
            $request->start_date ? Carbon::parse($request->start_date) : null,
            $request->end_date ? Carbon::parse($request->end_date) : null
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get top customers
     */
    public function topCustomers(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $data = $this->analyticsService->getTopCustomers(
            $request->input('limit', 10),
            $request->start_date ?  Carbon::parse($request->start_date) : null,
            $request->end_date ? Carbon::parse($request->end_date) : null
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get category performance
     */
    public function categoryPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $data = $this->analyticsService->getCategoryPerformance(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get sales by payment method
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $data = $this->analyticsService->getSalesByPaymentMethod(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get abandoned cart statistics
     */
    public function abandonedCarts(): JsonResponse
    {
        $data = $this->analyticsService->getAbandonedCartStats();

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Export analytics report
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $data = $this->analyticsService->exportAnalyticsReport(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'data' => $data,
        ]);
    }
}
