<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class AidValidUrl extends Constraint
{
    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
