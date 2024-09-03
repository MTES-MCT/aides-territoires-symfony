<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UrlExternalValid extends Constraint
{
    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
