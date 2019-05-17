<?php

namespace App\GangSheet\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static autoNest(array $data)
 * @method static contour(array $data)
 * @method static removeBg(array $data)
 */
class DripApps extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dripapps';
    }
}
