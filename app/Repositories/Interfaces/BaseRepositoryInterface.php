<?php

namespace App\Repositories\Interfaces;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Get all records
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get paginated records
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        array $relations = []
    ): LengthAwarePaginator;

    /**
     * Find a record by ID
     */
    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = []): Model;

    /**
     * Find by specific column
     */
    public function findBy(string $column, mixed $value, array $columns = ['*']): ?Model;

    /**
     * Find all by specific column
     */
    public function findAllBy(string $column, mixed $value, array $columns = ['*']): Collection;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record
     */
    public function delete(int $id): bool;

    /**
     * Force delete a record (for soft deletes)
     */
    public function forceDelete(int $id): bool;

    /**
     * Restore a soft deleted record
     */
    public function restore(int $id): bool;

    /**
     * Check if record exists
     */
    public function exists(int $id): bool;

    /**
     * Get count of records
     */
    public function count(): int;

    /**
     * Get records with where clause
     */
    public function where(array $conditions): Collection;

    /**
     * Get first record matching conditions
     */
    public function firstWhere(array $conditions): ?Model;

    /**
     * Create or update a record
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * Bulk insert records
     */
    public function insert(array $data): bool;
}
