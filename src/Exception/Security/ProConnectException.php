<?php

namespace App\Exception\Security;

class ProConnectException extends \Exception
{
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
