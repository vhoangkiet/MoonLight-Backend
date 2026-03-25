<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Get all records.
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Find a record by ID.
     */
    public function find(int|string $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Create a new record.
     */
    public function create(array $payload): Model;

    /**
     * Update an existing record.
     */
    public function update(int|string $id, array $payload): bool;

    /**
     * Delete a record by ID.
     */
    public function delete(int|string $id): bool;

    /**
     * Paginate records.
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator;

    /**
     * Apply dynamic filters to the model.
     *
     * @return Builder
     */
    public function applyFilters(array $filters = [], array $relations = []);

    /**
     * Find a record including trashed ones (if SoftDeletes is used).
     */
    public function findWithTrash(int|string $id): ?Model;
}
