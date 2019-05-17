<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\CustomerPasswordResetNotification;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'user_id', 'wc_user_id', 'email', 'name', 'password', 'phone'
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomerPasswordResetNotification($token));
    }

    public function loadDesignsCount(): void
    {
        $designCount = Design::where('customer_id', $this->id)
            ->count();

        $this['designCount'] = $designCount;
    }
}
