<?php

namespace App\Exceptions;

use Exception;

class DropboxUploadException extends Exception
{
    public function __construct()
    {
        parent::__construct("Unable to upload file to Dropbox");
    }
}
