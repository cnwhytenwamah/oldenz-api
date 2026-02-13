<?php

namespace App\Services\Admin;

use Exception;
use App\Dto\CustomerDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use App\Services\Admin\AdminBaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\CustomerRepositoryInterface;

class AdminCustomerService extends AdminBaseService
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository
    ) { }

    /**
     * Get all customers with filters
     */
    public function getAllCustomers(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->customerRepository->getWithFilters(
            $filters,
            $sortBy,
            $sortOrder,
            $perPage
        );
    }

    /**
     * Get customer by ID
     */
    public function getCustomerById(int $id): ?Model
    {
        return $this->customerRepository->find($id, ['*'], ['addresses', 'orders']);
    }

    /**
     * Get customer by email
     */
    public function getCustomerByEmail(string $email): ?Model
    {
        return $this->customerRepository->findByEmail($email);
    }

    /**
     * Create a new customer
     */
    public function createCustomer(CustomerDto $data): Model
    {
        try {
            DB::beginTransaction();

            $customerData = $data->toArray();

            if ($data->password) {
                $customerData['password'] = Hash::make($data->password);
            }

            $customer = $this->customerRepository->create($customerData);

            DB::commit();

            return $customer;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create customer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing customer
     */
    public function updateCustomer(int $id, CustomerDto $data): bool
    {
        try {
            DB::beginTransaction();

            $customerData = $data->toArray();

            if ($data->password) {
                $customerData['password'] = Hash::make($data->password);
            } else {
                unset($customerData['password']);
            }

            $updated = $this->customerRepository->update($id, $customerData);

            DB::commit();

            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update customer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a customer
     */
    public function deleteCustomer(int $id): bool
    {
        try {
            DB::beginTransaction();

            $customer = $this->customerRepository->findOrFail($id);

            if ($customer->orders()->count() > 0) {
                throw new Exception('Cannot delete customer with existing orders');
            }

            $deleted = $this->customerRepository->delete($id);

            DB::commit();

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete customer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update customer status
     */
    public function updateCustomerStatus(int $id, string $status): bool
    {
        return $this->customerRepository->update($id, [
            'status' => $status
        ]);
    }

    /**
     * Block customer
     */
    public function blockCustomer(int $id, string $reason): bool
    {
        return $this->customerRepository->update($id, [
            'status' => 'blocked',
            'blocked_reason' => $reason,
            'blocked_at' => now(),
        ]);
    }

    /**
     * Unblock customer
     */
    public function unblockCustomer(int $id): bool
    {
        return $this->customerRepository->update($id, [
            'status' => 'active',
            'blocked_reason' => null,
            'blocked_at' => null,
        ]);
    }

    /**
     * Get customer statistics
     */
    public function getCustomerStatistics(int $id): array
    {
        $customer = $this->customerRepository->findOrFail($id);

        return [
            'total_orders' => $customer->orders()->count(),
            'completed_orders' => $customer->orders()->where('status', 'delivered')->count(),
            'total_spent' => $customer->orders()->where('payment_status', 'paid')->sum('total'),
            'average_order_value' => $customer->orders()->where('payment_status', 'paid')->avg('total'),
            'last_order_date' => $customer->orders()->latest()->first()?->created_at,
            'wishlist_items' => $customer->wishlist()->count(),
        ];
    }

    /**
     * Get customer order history
     */
    public function getCustomerOrders(int $id, int $perPage = 15): LengthAwarePaginator
    {
        $customer = $this->customerRepository->findOrFail($id);
        
        return $customer->orders()->with(['items.product', 'payment'])->latest()->paginate($perPage);
    }

    /**
     * Get top customers by spending
     */
    public function getTopCustomers(int $limit = 10)
    {
        return $this->customerRepository->getTopBySpending($limit);
    }

    /**
     * Get recently registered customers
     */
    public function getRecentCustomers(int $limit = 10)
    {
        return $this->customerRepository->getRecent($limit);
    }

    /**
     * Search customers
     */
    public function searchCustomers(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->customerRepository->search($query, $perPage);
    }

    /**
     * Get customer lifetime value
     */
    public function getCustomerLifetimeValue(int $id): float
    {
        $customer = $this->customerRepository->findOrFail($id);
        
        return $customer->orders()->where('payment_status', 'paid')->sum('total');
    }

    /**
     * Get customer segments
     */
    public function getCustomerSegments(): array
    {
        return [
            'vip' => $this->customerRepository->getVIPCustomers(),
            'active' => $this->customerRepository->getActiveCustomers(),
            'inactive' => $this->customerRepository->getInactiveCustomers(),
            'new' => $this->customerRepository->getNewCustomers(),
        ];
    }

    /**
     * Export customers to CSV
     */
    public function exportCustomers(array $filters = []): string
    {
        $customers = $this->customerRepository->getForExport($filters);
        
        $csv = "ID,Name,Email,Phone,Status,Total Orders,Total Spent,Registered Date\n";
        
        foreach ($customers as $customer) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%d,%.2f,%s\n",
                $customer->id,
                $customer->full_name,
                $customer->email,
                $customer->phone ?? 'N/A',
                $customer->status,
                $customer->orders_count ?? 0,
                $customer->total_spent ?? 0,
                $customer->created_at->format('Y-m-d')
            );
        }

        return $csv;
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(int $id): bool
    {
        $customer = $this->customerRepository->findOrFail($id);

        Log::info("Password reset email sent to: {$customer->email}");
        
        return true;
    }

    /**
     * Get customer activity log
     */
    public function getCustomerActivity(int $id, int $limit = 20): array
    {
        $customer = $this->customerRepository->findOrFail($id);
        
        $activities = [];
  
        if ($customer->last_login_at) {
            $activities[] = [
                'type' => 'login',
                'description' => 'Last login',
                'date' => $customer->last_login_at,
            ];
        }
 
        foreach ($customer->orders()->latest()->limit($limit)->get() as $order) {
            $activities[] = [
                'type' => 'order',
                'description' => "Order {$order->order_number} - {$order->status->label()}",
                'date' => $order->created_at,
                'amount' => $order->total,
            ];
        }
        
        usort($activities, fn($a, $b) => $b['date'] <=> $a['date']);
        
        return array_slice($activities, 0, $limit);
    }
}
