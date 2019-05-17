<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InAppAuthSession extends Model
{
    protected $fillable = [
        'session_id',
        'token_id'
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(InAppAuthToken::class, 'token_id');
    }
}
