<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'data' => 'json'
    ];
}
