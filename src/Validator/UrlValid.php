<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UrlValid extends Constraint
{
    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
