<?php

namespace App\Exception\BusinessException;

class ResetPasswordNotFoundException extends \Exception
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
