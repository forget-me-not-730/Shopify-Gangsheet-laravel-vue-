<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'customer_id', 'wc_order_id', 'data', 'price', 'commission', 'status', 'phone',
        'email', 'name', 'status', 'state', 'city', 'street', 'zipcode'
    ];

    protected $casts = [
        'data' => 'json'
    ];

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    function designs()
    {
        return $this->hasMany(Design::class);
    }

    function product()
    {
        return $this->hasOneThrough(
            Product::class, Design::class, 'order_id', 'id', 'id', 'product_id'
        );
    }


    function getPaidAmountAttribute()
    {
        $paid_designs = $this->designs()->where('paid', true)->get();
        $price = 0;
        foreach ($paid_designs as $design) {
            $price += $design->commission;
        }

        return $price;
    }

    function getShippingMethod(): string
    {
        return $this->data['shipping_method'] ?? '';
    }
}
