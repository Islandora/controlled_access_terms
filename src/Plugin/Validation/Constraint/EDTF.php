<?php

namespace Drupal\controlled_access_terms\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * EDTF constraint plugin.
 *
 * @Constraint(
 *   id = "EDTF",
 *   label = @Translation("EDTF", context = "Validation"),
 *   type = "string",
 * )
 */
class EDTF extends Constraint {

  /**
   * Invalid format message template.
   *
   * @var string
   */
  public $invalid = '%value is not valid EDTF: %verbose';

}
