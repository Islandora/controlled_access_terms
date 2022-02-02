<?php

namespace Drupal\controlled_access_terms\Plugin\Validation\Constraint;

use Drupal\controlled_access_terms\EDTFUtils;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * EDTF validation handler.
 */
class EDTFValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof EDTF) {
      throw new UnexpectedTypeException($constraint, EDTF::class);
    }
    if (NULL === $value) {
      return;
    }

    foreach ($value->getValue() as $val) {
      foreach (EDTFUtils::validate($val, TRUE, TRUE, FALSE) as $error) {
        $this->context->buildViolation($constraint->invalid)
          ->setParameter('%value', $val)
          ->setParameter('%verbose', $error)
          ->addViolation();
      }
    }

  }

}
