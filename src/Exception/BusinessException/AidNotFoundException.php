<?php

namespace App\Exception\BusinessException;

class AidNotFoundException extends \Exception
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
