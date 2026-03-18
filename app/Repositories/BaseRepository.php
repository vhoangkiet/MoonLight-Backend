<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repository base để tránh lặp CRUD/query boilerplate.
 *
 * @template TModel of Model
 */
abstract class BaseRepository
{
    /**
     * @param  TModel  $model
     */
    public function __construct(protected Model $model)
    {
    }

    /**
     * @return Builder<TModel>
     */
    public function query(): Builder
    {
        /** @var Builder<TModel> $query */
        $query = $this->model->newQuery();

        return $query;
    }

    /**
     * @return TModel
     */
    public function findOrFail(string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model
    {
        /** @var TModel $created */
        $created = $this->query()->create($attributes);

        return $created;
    }

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    /**
     * @param  TModel  $model
     */
    public function delete(Model $model): void
    {
        $model->delete();
    }

    /**
     * @param  int  $perPage
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage);
    }
}

