<?php

namespace App\GangSheet\Facades;

use App\Models\InAppAuthToken;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getAuthorizeUrl(array $params)
 * @method static array authorizeStoreFromCode(string $code, array $options)
 * @method static InAppAuthToken getConnectedStoreToken(int $user_id, array $options = [])
 * @method static void revokeStoreAccess(int $user_id)
 */
class Dropbox extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dropbox';
    }
}
