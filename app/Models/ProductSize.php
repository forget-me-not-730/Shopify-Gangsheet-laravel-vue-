<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    protected $fillable = [
        'product_id',
        'woo_variant_id',
        'width',
        'label',
        'height',
        'unit',
        'price',
        'max_allowed_files',
        'pattern'
    ];

    protected $appends = [
        'maxAllowedFileCount',
        'pattern'
    ];

    function getMaxAllowedFileCountAttribute()
    {
        return $this->max_allowed_files;
    }

    function getPatternAttribute(): ?array
    {
        $data = spaces()->get("variants/{$this->id}.json");

        if (!empty($data)) {
            return json_decode($data, true);
        }

        return null;
    }

    function setPatternAttribute($value): void
    {
        if ($value) {
            spaces()->put("variants/{$this->id}.json", json_encode($value));
        } else {
            spaces()->delete("variants/{$this->id}.json");
        }
    }
}
