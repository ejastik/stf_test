<?php

namespace Platform\RestBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Slug extends Constraint
{
//    public $message = "The string `%wrong%` can not be used as slug (capable string is `%correct%`).";
    public $message = "Строка `%wrong%` не может быть использована как slug (допустимый slug: `%correct%`).";
}