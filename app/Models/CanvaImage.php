<?php

namespace App\Models;

use App\GangSheet\Models\CanvaImageModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanvaImage extends CanvaImageModel
{

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

}
