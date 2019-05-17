<?php

namespace App\Repositories;

class BaseRepository
{
    private $model;

    public function __construct($model = null)
    {
        if ($model) {
            $this->setModel($model);
        }
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }
}
