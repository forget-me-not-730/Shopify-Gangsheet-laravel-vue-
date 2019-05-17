<?php

namespace App\GangSheet\Facades;

use App\Models\InAppAuthToken;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getAuthorizeUrl(array $params)
 * @method static array authorizeCustomerFromCode(string $code, array $options)
 * @method static InAppAuthToken getConnectedCustomerToken(array $options)
 * @method static void revokeCustomerAccess(array $options)
 * @method static getDesignsByAccessToken(string $accessToken, string $query = '')
 * @method static createDesignExport($designId, $width, $height, $accessToken)
 * @method static getDesignExport($exportId, $accessToken)
 */
class Canva extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'canva';
    }
}
