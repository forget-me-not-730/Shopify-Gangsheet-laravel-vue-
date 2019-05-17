<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Builder
 */
class InAppAuthToken extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'identifier',
        'email',
        'name',
        'access_token',
        'refresh_token',
        'expires_in',
        'grant_type'
    ];

    public function isExpired(): bool
    {
        return $this->updated_at->addSeconds($this->expires_in - 60)->isPast();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(InAppAuthSession::class, 'token_id');
    }
}
