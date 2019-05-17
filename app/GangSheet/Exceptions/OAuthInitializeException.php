<?php

namespace App\GangSheet\Exceptions;

use \Exception;

class OAuthInitializeException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
