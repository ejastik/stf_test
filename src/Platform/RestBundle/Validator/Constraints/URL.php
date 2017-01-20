<?php

namespace Platform\RestBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class URL extends Constraint
{
//    public $message = "The string `%wrong%` can not be used as URL (capable string is `%correct%`).";
    public $message = "Строка `%wrong%` не может быть использована как URL (допустимый URL: `%correct%`).";
}