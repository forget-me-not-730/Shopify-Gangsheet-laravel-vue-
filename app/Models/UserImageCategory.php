<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserImageCategory extends Model
{
    protected $fillable = [
        'user_id', 'parent_id', 'name', 'status', 'description', 'order', 'color_overlay'
    ];

    protected $appends = [
        'image_url'
    ];

    function getImageUrlAttribute(): string
    {
        $path = "gallery/{$this->user_id}/categories/{$this->id}.png";
        return spaces()->url($path);
    }

    function images()
    {
        return $this->hasMany(UserImage::class, 'category_id');
    }

    function parent(): BelongsTo
    {
        return $this->belongsTo(UserImageCategory::class, 'parent_id', 'id');
    }

    function children(): HasMany
    {
        return $this->hasMany(UserImageCategory::class, 'parent_id', 'id');
    }

    function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
