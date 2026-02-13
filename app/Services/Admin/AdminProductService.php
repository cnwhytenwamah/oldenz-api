<?php

namespace App\Services\Admin;

use Exception;
use App\Dto\ProductDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\ProductRepositoryInterface;


class ProductService extends AdminBaseService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Get all products with filters
     */
    public function getAllProducts(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->productRepository->getWithFilters(
            $filters,
            $sortBy,
            $sortOrder,
            $perPage
        );
    }

    /**
     * Get product by ID
     */
    public function getProductById(int $id): ?Model
    {
        return $this->productRepository->find($id, ['*'], ['categories', 'images', 'variants']);
    }

    /**
     * Create a new product
     */
    public function createProduct(ProductDto $data): Model
    {
        try {
            DB::beginTransaction();

            $product = $this->productRepository->create($data->toArray());

            if ($data->categoryIds) {
                $product->categories()->attach($data->categoryIds);
            }

            if ($data->images) {
                $this->handleProductImages($product, $data->images);
            }

            DB::commit();

            return $product->load(['categories', 'images']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create product: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing product
     */
    public function updateProduct(int $id, ProductDto $data): bool
    {
        try {
            DB::beginTransaction();

            $product = $this->productRepository->findOrFail($id);

            $updated = $this->productRepository->update($id, $data->toArray());

            if (!$updated) {
                throw new Exception('Failed to update product');
            }

            if ($data->categoryIds !== null) {
                $product->categories()->sync($data->categoryIds);
            }

            if ($data->images !== null) {
                $this->handleProductImages($product, $data->images);
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update product: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a product
     */
    public function deleteProduct(int $id): bool
    {
        try {
            DB::beginTransaction();

            $product = $this->productRepository->findOrFail($id);
            
            $product->images()->delete();
            
            $product->variants()->delete();
            
            $product->categories()->detach();

            $deleted = $this->productRepository->delete($id);

            DB::commit();

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete product: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle product active status
     */
    public function toggleProductStatus(int $id): bool
    {
        $product = $this->productRepository->findOrFail($id);
        
        return $this->productRepository->update($id, [
            'is_active' => !$product->is_active
        ]);
    }

    /**
     * Update product stock
     */
    public function updateStock(int $id, int $quantity): bool
    {
        return $this->productRepository->updateStock($id, $quantity);
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts()
    {
        return $this->productRepository->getLowStock();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStockProducts()
    {
        return $this->productRepository->getOutOfStock();
    }

    /**
     * Bulk update products
     */
    public function bulkUpdate(array $productIds, array $data): int
    {
        $updatedCount = 0;

        foreach ($productIds as $productId) {
            if ($this->productRepository->update($productId, $data)) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * Duplicate a product
     */
    public function duplicateProduct(int $id): Model
    {
        try {
            DB::beginTransaction();

            $originalProduct = $this->productRepository->findOrFail($id, ['*'], ['categories', 'images', 'variants']);

            // Create duplicate with modified data
            $duplicateData = $originalProduct->toArray();
            $duplicateData['name'] = $originalProduct->name . ' (Copy)';
            $duplicateData['slug'] = $originalProduct->slug . '-copy-' . time();
            $duplicateData['sku'] = $originalProduct->sku . '-COPY-' . time();
            
            unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at']);

            $duplicate = $this->productRepository->create($duplicateData);

            $duplicate->categories()->attach($originalProduct->categories->pluck('id'));

            foreach ($originalProduct->images as $image) {
                $duplicate->images()->create([
                    'path' => $image->path,
                    'url' => $image->url,
                    'thumbnail_url' => $image->thumbnail_url,
                    'sort_order' => $image->sort_order,
                    'is_primary' => $image->is_primary,
                    'alt_text' => $image->alt_text,
                ]);
            }

            DB::commit();

            return $duplicate->load(['categories', 'images']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to duplicate product: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle product images
     */
    private function handleProductImages(Model $product, array $images): void
    {
        
        foreach ($images as $index => $image) {
            $product->images()->create([
                'path' => $image['path'] ?? '',
                'url' => $image['url'] ?? '',
                'thumbnail_url' => $image['thumbnail_url'] ?? null,
                'sort_order' => $index,
                'is_primary' => $index === 0,
                'alt_text' => $image['alt_text'] ?? $product->name,
            ]);
        }
    }
}
