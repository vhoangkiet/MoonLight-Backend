<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    /**
     * BaseRepository constructor.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function find(int|string $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->find($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $payload): Model
    {
        return DB::transaction(function () use ($payload) {
            $model = $this->model->newInstance($payload);
            $model->save();

            return $model->fresh();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function update(int|string $id, array $payload): bool
    {
        return DB::transaction(function () use ($id, $payload) {
            $model = $this->find($id);

            if (! $model) {
                return false;
            }

            return $model->update($payload);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int|string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $model = $this->find($id);

            if (! $model) {
                return false;
            }

            return $model->delete();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        return $this->model->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * {@inheritDoc}
     */
    public function applyFilters(array $filters = [], array $relations = [])
    {
        $query = $this->model->newQuery()->with($relations);

        foreach ($filters as $key => $value) {
            if (method_exists($this->model, 'scope'.ucfirst($key))) {
                $query->$key($value);
            } elseif (in_array($key, $this->model->getFillable()) || $key === 'id') {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function findWithTrash(int|string $id): ?Model
    {
        if (method_exists($this->model, 'withTrashed')) {
            return $this->model->withTrashed()->find($id);
        }

        return $this->find($id);
    }
}
