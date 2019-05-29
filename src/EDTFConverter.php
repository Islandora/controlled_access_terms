<?php

namespace Drupal\controlled_access_terms;

use Drupal\rdf\CommonDataConverter;

/**
 * {@inheritdoc}
 */
class EDTFConverter extends CommonDataConverter {

  /**
   * Converts an EDTF text field into an ISO 8601 timestamp string.
   *
   * It assumes the earliest valid date for approximations and intervals.
   *
   * @param mixed $data
   *   The array containing the 'value' element.
   *
   * @return string
   *   Returns the ISO 8601 timestamp.
   */
  public static function datetimeIso8601Value($data) {

    // Take first possible date.
    $date = preg_split('/(,|\.\.|\/)/', trim($data['value'], '{}[]'))[0];

    return EDTFUtils::iso8601Value($date);

  }

  /**
   * Converts an EDTF text field into an ISO 8601 timestamp string.
   *
   * It assumes the earliest valid date for approximations and intervals.
   *
   * @param mixed $data
   *   The array containing the 'value' element.
   *
   * @return string
   *   Returns the ISO 8601 date.
   */
  public static function dateIso8601Value($data) {

    return explode('T', EDTFConverter::datetimeIso8601Value($data))[0];

  }

}
