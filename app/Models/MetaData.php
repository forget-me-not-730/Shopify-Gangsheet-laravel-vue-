<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaData extends Model
{

    public $timestamps = false;

    protected $fillable = ['model_type', 'model_id', 'key', 'value'];

    public function model()
    {
        return $this->morphTo();
    }
}
