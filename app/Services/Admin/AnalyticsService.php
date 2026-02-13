<?php

namespace App\Services\Admin;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\Interfaces\CustomerRepositoryInterface;

class AnalyticsService extends AdminBaseService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected ProductRepositoryInterface $productRepository,
        protected CustomerRepositoryInterface $customerRepository
    ) { }

    /**
     * Get dashboard overview statistics
     */
    public function getDashboardOverview(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? now()->startOfMonth();
        $endDate = $filters['end_date'] ?? now()->endOfMonth();

        return [
            'revenue' => $this->getRevenueMetrics($startDate, $endDate),
            'orders' => $this->getOrderMetrics($startDate, $endDate),
            'customers' => $this->getCustomerMetrics($startDate, $endDate),
            'products' => $this->getProductMetrics(),
        ];
    }

    /**
     * Get revenue metrics
     */
    public function getRevenueMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $currentRevenue = $this->orderRepository->getTotalRevenue($startDate, $endDate);
        
        // Calculate previous period for comparison
        $periodDays = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($periodDays);
        $previousEnd = $startDate->copy()->subDay();
        $previousRevenue = $this->orderRepository->getTotalRevenue($previousStart, $previousEnd);

        $growth = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        return [
            'total' => round($currentRevenue, 2),
            'previous_period' => round($previousRevenue, 2),
            'growth_percentage' => round($growth, 2),
            'average_order_value' => round(
                $this->orderRepository->getAverageOrderValue($startDate, $endDate), 
                2
            ),
        ];
    }

    /**
     * Get order metrics
     */
    public function getOrderMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total' => $this->orderRepository->countByDateRange($startDate, $endDate),
            'pending' => $this->orderRepository->countByStatus('pending'),
            'confirmed' => $this->orderRepository->countByStatus('confirmed'),
            'processing' => $this->orderRepository->countByStatus('processing'),
            'shipped' => $this->orderRepository->countByStatus('shipped'),
            'delivered' => $this->orderRepository->countByStatus('delivered'),
            'cancelled' => $this->orderRepository->countByStatus('cancelled'),
            'completion_rate' => $this->calculateOrderCompletionRate($startDate, $endDate),
        ];
    }

    /**
     * Get customer metrics
     */
    public function getCustomerMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $totalCustomers = $this->customerRepository->count();
        $newCustomers = $this->customerRepository->countByDateRange($startDate, $endDate);

        return [
            'total' => $totalCustomers,
            'new' => $newCustomers,
            'active' => $this->customerRepository->getActiveCustomersCount($startDate, $endDate),
            'retention_rate' => $this->calculateCustomerRetentionRate($startDate, $endDate),
        ];
    }

    /**
     * Get product metrics
     */
    public function getProductMetrics(): array
    {
        return [
            'total' => $this->productRepository->count(),
            'active' => $this->productRepository->countActive(),
            'low_stock' => $this->productRepository->getLowStock()->count(),
            'out_of_stock' => $this->productRepository->getOutOfStock()->count(),
        ];
    }

    /**
     * Get revenue chart data
     */
    public function getRevenueChart(Carbon $startDate, Carbon $endDate, string $interval = 'daily'): array
    {
        $data = DB::table('orders')->selectRaw($this->getDateGrouping($interval) . ' as period, SUM(total) as revenue')->where('payment_status', 'paid')->whereBetween('created_at', [$startDate, $endDate])->groupBy('period')->orderBy('period')->get();

        return [
            'labels' => $data->pluck('period')->toArray(),
            'values' => $data->pluck('revenue')->map(fn($v) => round($v, 2))->toArray(),
        ];
    }

    /**
     * Get orders chart data
     */
    public function getOrdersChart(Carbon $startDate, Carbon $endDate, string $interval = 'daily'): array
    {
        $data = DB::table('orders')->selectRaw($this->getDateGrouping($interval) . ' as period, COUNT(*) as count')->whereBetween('created_at', [$startDate, $endDate])->groupBy('period')->orderBy('period')->get();

        return [
            'labels' => $data->pluck('period')->toArray(),
            'values' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Get best selling products
     */
    public function getBestSellingProducts(int $limit = 10, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = DB::table('order_items')->join('orders', 'order_items.order_id', '=', 'orders.id')->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.total) as total_revenue')
            )->where('orders.payment_status', 'paid');

        if ($startDate && $endDate) {
            $query->whereBetween('orders.created_at', [$startDate, $endDate]);
        }

        return $query->groupBy('products.id', 'products.name', 'products.sku')->orderByDesc('total_sold')->limit($limit)->get()->toArray();
    }

    /**
     * Get top customers
     */
    public function getTopCustomers(int $limit = 10, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = DB::table('orders')->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->select(
                'customers.id',
                'customers.first_name',
                'customers.last_name',
                'customers.email',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total) as total_spent')
            )->where('orders.payment_status', 'paid');

        if ($startDate && $endDate) {
            $query->whereBetween('orders.created_at', [$startDate, $endDate]);
        }

        return $query->groupBy('customers.id', 'customers.first_name', 'customers.last_name', 'customers.email')->orderByDesc('total_spent')->limit($limit)->get()->toArray();
    }

    /**
     * Get category performance
     */
    public function getCategoryPerformance(Carbon $startDate, Carbon $endDate): array
    {
        return DB::table('order_items')->join('orders', 'order_items.order_id', '=', 'orders.id')->join('category_product', 'order_items.product_id', '=', 'category_product.product_id')->join('categories', 'category_product.category_id', '=', 'categories.id')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('SUM(order_items.quantity) as units_sold'),
                DB::raw('SUM(order_items.total) as revenue')
            )->where('orders.payment_status', 'paid')->whereBetween('orders.created_at', [$startDate, $endDate])->groupBy('categories.id', 'categories.name')->orderByDesc('revenue')->get()->toArray();
    }

    /**
     * Get sales by payment method
     */
    public function getSalesByPaymentMethod(Carbon $startDate, Carbon $endDate): array
    {
        return DB::table('payments')->join('orders', 'payments.order_id', '=', 'orders.id')
            ->select(
                'payments.payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(payments.amount) as total_amount')
            )->where('payments.status', 'successful')->whereBetween('orders.created_at', [$startDate, $endDate])->groupBy('payments.payment_method')->get()->toArray();
    }

    /**
     * Get abandoned cart statistics
     */
    public function getAbandonedCartStats(): array
    {
        $totalCarts = DB::table('carts')->where('status', 'active')->count();
        $abandonedCarts = DB::table('carts')->where('status', 'abandoned')->count();
        $potentialRevenue = DB::table('cart_items')->join('carts', 'cart_items.cart_id', '=', 'carts.id')->where('carts.status', 'abandoned')->sum(DB::raw('cart_items.quantity * cart_items.price'));

        return [
            'total_active_carts' => $totalCarts,
            'abandoned_carts' => $abandonedCarts,
            'abandonment_rate' => $totalCarts > 0 ? round(($abandonedCarts / $totalCarts) * 100, 2) : 0,
            'potential_revenue' => round($potentialRevenue, 2),
        ];
    }

    /**
     * Calculate order completion rate
     */
    private function calculateOrderCompletionRate(Carbon $startDate, Carbon $endDate): float
    {
        $totalOrders = $this->orderRepository->countByDateRange($startDate, $endDate);
        $deliveredOrders = DB::table('orders')->where('status', 'delivered')->whereBetween('created_at', [$startDate, $endDate])->count();

        return $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 2) : 0;
    }

    /**
     * Calculate customer retention rate
     */
    private function calculateCustomerRetentionRate(Carbon $startDate, Carbon $endDate): float
    {
        $customersWithMultipleOrders = DB::table('orders')->select('customer_id')->whereBetween('created_at', [$startDate, $endDate])->groupBy('customer_id')->havingRaw('COUNT(*) > 1')->count();
        $totalCustomers = DB::table('orders')->whereBetween('created_at', [$startDate, $endDate])->distinct('customer_id')->count();

        return $totalCustomers > 0 
            ? round(($customersWithMultipleOrders / $totalCustomers) * 100, 2) 
            : 0;
    }

    /**
     * Get date grouping SQL based on interval
     */
    private function getDateGrouping(string $interval): string
    {
        return match ($interval) {
            'hourly' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00')",
            'daily' => "DATE(created_at)",
            'weekly' => "DATE_FORMAT(created_at, '%Y-%u')",
            'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
            'yearly' => "YEAR(created_at)",
            default => "DATE(created_at)",
        };
    }

    /**
     * Export analytics report
     */
    public function exportAnalyticsReport(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'overview' => $this->getDashboardOverview([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]),
            'best_selling_products' => $this->getBestSellingProducts(20, $startDate, $endDate),
            'top_customers' => $this->getTopCustomers(20, $startDate, $endDate),
            'category_performance' => $this->getCategoryPerformance($startDate, $endDate),
            'payment_methods' => $this->getSalesByPaymentMethod($startDate, $endDate),
        ];
    }
}
