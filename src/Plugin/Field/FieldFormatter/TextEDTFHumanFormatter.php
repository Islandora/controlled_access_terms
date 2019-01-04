<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'TextEDTFHumanFormatter'.
 *
 * Only supports EDTF through level 1.
 *
 * @FieldFormatter(
 *   id = "text_edtf_human",
 *   label = @Translation("EDTF (L1) for Humans"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class TextEDTFHumanFormatter extends EDTFFormatter {

}
