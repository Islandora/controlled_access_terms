<?php

namespace Drupal\controlled_access_terms\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Constraint(
 *   id = "EDTF",
 *   label = @Translation("EDTF", context = "Validation"),
 *   type = "string",
 * )
 */
class EDTF extends Constraint {

  public $invalid = '%value is not valid EDTF: %verbose';

}
