<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository
{
    public static function getCustomer($options): ?Customer
    {
        if (!empty($options['customer_id'])) {
            $customer = Customer::find($options['customer_id']);
        }

        if (empty($customer)) {
            $customer = Customer::leftJoin('customer_sessions', 'customers.id', '=', 'customer_sessions.customer_id')
                ->where('customer_sessions.session_id', $options['session_id'])
                ->first();
        }

        return $customer ?? null;
    }
}
