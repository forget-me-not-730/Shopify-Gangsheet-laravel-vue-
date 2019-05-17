<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'transaction_id', 'amount', 'status'
    ];

    function user(){
        return $this->belongsTo(User::class);
    }

    function getPaymentUrlAttribute(){
        return 'https://dashboard.stripe.com/payments/'.$this->transaction_id;
    }
}
