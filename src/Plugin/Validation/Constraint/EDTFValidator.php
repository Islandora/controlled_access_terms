<?php

namespace Drupal\controlled_access_terms\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validation.
 */
class EDTFValidator extends ConstraintValidator {

  public function validate($value, Constraint $constraint) {
    $settings = $value->getFieldDefinition();
    ddm($settings, 'qwer');
    dsm($settings, 'asdf');

    // TODO: The validation things, using the field's configuration.
  }

}
