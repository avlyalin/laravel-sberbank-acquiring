<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Поиск модели через id
     *
     * @param int $id
     * @param string[] $columns
     *
     * @return Model
     */
    public function find(int $id, $columns = ['*']): Model
    {
        return $this->model->newQuery()->find($id, $columns);
    }

    /**
     * Поиск модели через id или выброс исключения
     *
     * @param int $id
     * @param string[] $columns
     *
     * @return Model
     */
    public function findOrFail(int $id, $columns = ['*']): Model
    {
        return $this->model->newQuery()->findOrFail($id, $columns);
    }
}
