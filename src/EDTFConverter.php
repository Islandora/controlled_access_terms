<?php

namespace Drupal\controlled_access_terms;

use Drupal\rdf\CommonDataConverter;

/**
 * {@inheritdoc}
 */
class EDTFConverter extends CommonDataConverter {

  /**
   * Northern hemisphere season map.
   *
   * @var array
   */
  private $seasonMapNorth = [
  // Spring => March.
    '21' => '03',
  // Summer => June.
    '22' => '06',
  // Autumn => September.
    '23' => '09',
  // Winter => December.
    '24' => '12',
  ];

  /**
   * Southern hemisphere season map.
   *
   * (Currently unused until a config for this is established.)
   *
   * @var array
   */
  private $seasonMapSouth = [
  // Spring => September.
    '21' => '03',
  // Summer => December.
    '22' => '06',
  // Autumn => March.
    '23' => '09',
  // Winter => June.
    '24' => '12',
  ];

  /**
   * Converts an EDTF text field into an ISO 8601 timestamp string.
   *
   * It assumes the earliest valid date for approximations and intervals.
   *
   * @param array $data
   *   The array containing the 'value' element.
   *
   * @return string
   *   Returns the ISO 8601 timestamp.
   */
  public static function dateIso8601Value(array $data) {
    $date = explode('/', $data['value'])[0];

    // Strip approximations/uncertainty.
    $date = str_replace(['?', '~'], '', $date);

    // Replace unspecified.
    // Month/day.
    $date = str_replace('-uu', '-01', $date);
    // Zero-Year in decade/century.
    $date = str_replace('u', '0', $date);

    // Seasons map.
    list($year, $month, $day) = explode('-', $date, 3);
    // Digit Seasons.
    if (in_array($month, ['21', '22', '23', '24'])) {
      // TODO: Make hemisphere seasons configurable.
      $season_mapping = $seasonMapNorth;
      $month = $season_mapping[$month];
      $date = implode('-', array_filter([$year, $month, $day]));
    }

    return $date;

  }

}
