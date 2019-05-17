<?php

namespace App\Exceptions;

use Exception;

class DesignNotEditableException extends Exception
{
    public function __construct(string $message = "Design is not editable, Send a request to edit the design.")
    {
        parent::__construct($message);
    }
}
