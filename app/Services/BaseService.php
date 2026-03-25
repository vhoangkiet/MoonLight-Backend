<?php

namespace App\Services;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    protected BaseRepositoryInterface $repository;

    /**
     * BaseService constructor.
     */
    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all records.
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Find a record by ID.
     */
    public function find(int|string $id): ?Model
    {
        return $this->repository->find($id);
    }

    /**
     * Create a new record.
     */
    public function create(array $payload): Model
    {
        try {
            $model = $this->repository->create($payload);
            Log::info(static::class.' created a new record', ['id' => $model->id, 'payload' => $payload]);

            return $model;
        } catch (\Exception $e) {
            Log::error(static::class.' failed to create record', ['error' => $e->getMessage(), 'payload' => $payload]);
            throw $e;
        }
    }

    /**
     * Update an existing record.
     */
    public function update(int|string $id, array $payload): bool
    {
        try {
            $updated = $this->repository->update($id, $payload);
            if ($updated) {
                Log::info(static::class.' updated record', ['id' => $id, 'payload' => $payload]);
            }

            return $updated;
        } catch (\Exception $e) {
            Log::error(static::class.' failed to update record', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete a record by ID.
     */
    public function delete(int|string $id): bool
    {
        try {
            $deleted = $this->repository->delete($id);
            if ($deleted) {
                Log::info(static::class.' deleted record', ['id' => $id]);
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error(static::class.' failed to delete record', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Paginate records.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
