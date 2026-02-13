<?php

namespace App\Repositories\Eloquents;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var Model
     */
    protected Model $model;

    /**
     * BaseRepository constructor
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    /**
     * Get paginated records
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        array $relations = []
    ): LengthAwarePaginator {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    /**
     * Find a record by ID
     */
    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->find($id, $columns);
    }

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = []): Model
    {
        return $this->model->with($relations)->findOrFail($id, $columns);
    }

    /**
     * Find by specific column
     */
    public function findBy(string $column, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->model->where($column, $value)->first($columns);
    }

    /**
     * Find all by specific column
     */
    public function findAllBy(string $column, mixed $value, array $columns = ['*']): Collection
    {
        return $this->model->where($column, $value)->get($columns);
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->find($id);
        
        if (!$record) {
            return false;
        }

        return $record->update($data);
    }

    /**
     * Delete a record
     */
    public function delete(int $id): bool
    {
        $record = $this->find($id);
        
        if (!$record) {
            return false;
        }

        return $record->delete();
    }

    /**
     * Force delete a record
     */
    public function forceDelete(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);
        
        if (!$record) {
            return false;
        }

        return $record->forceDelete();
    }

    /**
     * Restore a soft deleted record
     */
    public function restore(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);
        
        if (!$record) {
            return false;
        }

        return $record->restore();
    }

    /**
     * Check if record exists
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Get count of records
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Get records with where clause
     */
    public function where(array $conditions): Collection
    {
        return $this->model->where($conditions)->get();
    }

    /**
     * Get first record matching conditions
     */
    public function firstWhere(array $conditions): ?Model
    {
        return $this->model->where($conditions)->first();
    }

    /**
     * Create or update a record
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Bulk insert records
     */
    public function insert(array $data): bool
    {
        return $this->model->insert($data);
    }

    /**
     * Begin database transaction
     */
    protected function beginTransaction(): void
    {
        \DB::beginTransaction();
    }

    /**
     * Commit database transaction
     */
    protected function commit(): void
    {
        \DB::commit();
    }

    /**
     * Rollback database transaction
     */
    protected function rollback(): void
    {
        \DB::rollBack();
    }
}