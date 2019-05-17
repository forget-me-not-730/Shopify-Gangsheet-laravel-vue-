<?php

namespace App\GangSheet\Exceptions;

use \Exception;

class GenerationException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
