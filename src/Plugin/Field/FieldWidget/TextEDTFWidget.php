<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'text_edtf' widget.
 *
 * Validates text values for compliance with EDTF 1.0, level 1.
 * http://www.loc.gov/standards/datetime/pre-submission.html.
 *
 * // TODO: maybe some day support level 2.
 *
 * @FieldWidget(
 *   id = "text_edtf",
 *   label = @Translation("Extended Date Time Format, Level 1"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class TextEDTFWidget extends EDTFWidget {

}
