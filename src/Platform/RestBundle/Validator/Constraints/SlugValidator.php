<?php

namespace Platform\RestBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Cocur\Slugify\Slugify;

class SlugValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $slug = new Slugify(['lowercase' => false]);
        $correct = $slug->slugify($value, '_');

        if ($value != $correct)
        {
            $this->context->addViolation($constraint->message, ['%wrong%' => $value, '%correct%' => $correct]);
        }
    }
}