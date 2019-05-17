<?php

namespace App\GangSheet\Facades;

use App\Models\InAppAuthToken;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getAuthorizeUrl(array $params)
 * @method static array authorizeStoreFromCode(string $code, array $options)
 * @method static array authorizeCustomerFromCode(string $code, array $options)
 * @method static void revokeStoreAccess(int $user_id)
 * @method static void revokeCustomerAccess(array $options)
 * @method static InAppAuthToken getConnectedStoreToken(int $user_id, array $options = [])
 * @method static InAppAuthToken getConnectedCustomerToken(array $options)
 */
class Google extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'google';
    }
}
