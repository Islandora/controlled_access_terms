<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;

/**
 * Implements a Extended DateTime Format field.
 *
 * @FieldType(
 *   id = "edtf",
 *   label = @Translation("EDTF"),
 *   module = "controlled_access_terms",
 *   description = @Translation("Extended Date Time Format field"),
 *   default_formatter = "edtf_default",
 *   default_widget = "edtf_default",
 * )
 */
class ExtendedDateTimeFormat extends StringItem {

}
