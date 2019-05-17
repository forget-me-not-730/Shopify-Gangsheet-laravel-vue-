<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'session_id'
    ];
}
