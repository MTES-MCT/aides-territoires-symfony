<?php

namespace App\Exception\NotFoundException;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AidNotFoundException extends NotFoundHttpException
{
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
