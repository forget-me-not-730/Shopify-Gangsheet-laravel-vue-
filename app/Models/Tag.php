<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    function userImages(): BelongsToMany
    {
        return $this->belongsToMany(UserImage::class, 'user_image_tags', 'tag_id', 'user_image_id');
    }
}
