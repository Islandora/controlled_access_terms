<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\controlled_access_terms\EDTFUtils;

/**
 * Plugin implementation of the 'TextEDTFFormatter'.
 *
 * Only supports EDTF through level 1.
 *
 * @FieldFormatter(
 *   id = "edtf_default",
 *   label = @Translation("Default EDTF formatter"),
 *   field_types = {
 *     "edtf"
 *   }
 * )
 */
class EDTFFormatter extends FormatterBase {

  /**
   * Various delimiters.
   *
   * @var array
   */
  private $DELIMITERS = [
    'dash'   => '-',
    'stroke' => '/',
    'period' => '.',
    'space'  => ' ',
  ];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
    // ISO 8601 bias.
      'date_separator' => 'dash',
      'date_order' => 'big_endian',
      'month_format' => 'mm',
      'day_format' => 'dd',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['date_separator'] = [
      '#title' => t('Date Separator'),
      '#type' => 'select',
      '#description' => "Select the separator between date elements.",
      '#default_value' => $this->getSetting('date_separator'),
      '#options' => [
        'dash' => t("Dash '-'"),
        'stroke' => t("Stroke '\'"),
        'period' => t("Period '.'"),
        'space' => t("Space ' '"),
      ],
    ];
    $form['date_order'] = [
      '#title' => t('Date Order'),
      '#type' => 'select',
      '#description' => "Select the separator between date elements.",
      '#default_value' => $this->getSetting('date_order'),
      '#options' => [
        'big_endian' => t('Big-endian (year, month, day)'),
        'little_endian' => t('Little-endian (day, month, year)'),
        'middle_endian' => t('Middle-endian (month, day, year)'),
      ],
    ];
    $form['month_format'] = [
      '#title' => t('Month Format'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('month_format'),
      '#options' => [
        'mm' => t('two-digit month, e.g. 04'),
        'm' => t('one-digit month for months below 10, e.g. 4'),
        'mmm' => t('three-letter abbreviation for month, Apr'),
        'mmmm' => t('month spelled out in full, e.g. April'),
      ],
    ];
    $form['day_format'] = [
      '#title' => t('Day Format'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('day_format'),
      '#options' => [
        'dd' => t('two-digit day of the month, e.g. 02'),
        'd' => t('one-digit day of the month for days below 10, e.g. 2'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $example_date = $this->formatDate('1996-04-22');
    $summary[] = t('Date Format Example: @date', ['@date' => $example_date]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Interval.
      if (strpos($item->value, '/') !== FALSE) {
        list($begin, $end) = explode('/', $item->value);

        if (empty($begin) || $begin === '..') {
          $formatted_begin = "open start";
        }
        else {
          $formatted_begin = $this->formatDate($begin);
        }

        if (empty($end) || $end === '..') {
          $formatted_end = "open end";
        }
        else {
          $formatted_end = $this->formatDate($end);
        }

        $element[$delta] = [
          '#markup' => t('@begin to @end', [
            '@begin' => $formatted_begin,
            '@end' => $formatted_end,
          ]),
        ];
        continue;
      }
      // Sets.
      if (strpos($item->value, '[') !== FALSE || strpos($item->value, '{') !== FALSE) {
        $set_qualifier = (strpos($item->value, '[') !== FALSE) ? t('one of the dates:') : t('all of the dates:');
        foreach (preg_split('/(,|\.\.)/', trim($item->value, '{}[]')) as $date) {
          $formatted_dates[] = $this->formatDate($date);
        }
        $element[$delta] = [
          '#markup' => t('@qualifier @list', [
            '@qualifier' => $set_qualifier,
            '@list' => implode(', ', $formatted_dates),
          ]),
        ];
        continue;
      }

      $element[$delta] = [
        '#markup' => $this->formatDate($item->value),
      ];
    }
    return $element;
  }

  /**
   * Create a date format string.
   *
   * @param string $edtf_text
   *   The date to format.
   *
   * @return string
   *   The date in EDTF format.
   */
  protected function formatDate($edtf_text) {

    list($date, $time) = explode('T', $datetime_str);

    // Formatted versions of the date elements.
    $year = '';
    $month = '';
    $day = '';

    preg_match(EDTFUtils::DATE_PARSE_REGEX, $date, $parsed_date);
    $parsed_date[EDTFUtils::YEAR_BASE] = EDTFUtils::expand_year($parsed_date[EDTFUtils::YEAR_FULL], $parsed_date[EDTFUtils::YEAR_BASE], $parsed_date[EDTFUtils::YEAR_EXPONENT]);
    $settings = $this->getSettings();

    // Unspecified.
    $unspecified = [];
    if (strpos($parsed_date[EDTFUtils::YEAR_BASE], 'XXXX') !== FALSE) {
      $unspecified[] = t('year');
    }
    elseif (strpos($parsed_date[EDTFUtils::YEAR_BASE], 'XXX') !== FALSE) {
      $unspecified[] = t('century');
    }
    elseif (strpos($parsed_date[EDTFUtils::YEAR_BASE], 'XX') !== FALSE) {
      $unspecified[] = t('decade');
    }
    elseif (strpos($parsed_date[EDTFUtils::YEAR_BASE], 'XXXX') !== FALSE) {
      $unspecified[] = t('year');
    }
    // Clean-up unspecified year/decade.
    $year = str_replace('X', '0', $parsed_date[EDTFUtils::YEAR_BASE]);

    if (array_key_exists(self::MONTH, $parsed_date) && strpos($parsed_date[EDTFUtils::MONTH], 'X') !== FALSE) {
      $unspecified[] = t('month');
      // IF 'mm', do nothing, it is already in this format.
      if ($settings['month_format'] === 'mmm' || $settings['month_format'] === 'mmmm') {
        $month = EDTFUtils::MONTHS_MAP[$parsed_date[EDTFUtils::MONTH]][$settings['month_format']];
      }
      if ($settings['month_format'] === 'm') {
        $month = ltrim($parsed_date[EDTFUtils::MONTH], ' 0');
      }
    }
    if (array_key_exists(self::DAY, $parsed_date) && strpos($parsed_date[EDTFUtils::DAY], 'X') !== FALSE) {
      $unspecified[] = t('day');
      if ($settings['day_format'] === 'd') {
        $day = ltrim($parsed_date[EDTFUtils::DAY], ' 0');
      }
      else {
        $day = $parsed_date[EDTFUtils::DAY];
      }
    }

    // TODO: Qualified.
    // Put the parts back together.
    if ($settings['date_order'] === 'little_endian') {
      $parts_in_order = [$day, $month, $year];
    }
    elseif ($settings['date_order'] === 'middle_endian') {
      $parts_in_order = [$month, $day, $year];
    }
    else {
      // Big Endian by default.
      $parts_in_order = [$year, $month, $day];
    }

    if ($settings['date_order'] === 'middle_endian' && !preg_match('/\d/', $month) && !empty(array_filter([$month, $day]))) {
      $formatted_date = "$month $day, $year";
    }
    else {
      $formatted_date = implode($this->DELIMITERS[$settings['date_separator']], array_filter($parts_in_order));
    }

    // TODO: Time.
    // Return sprintf($qualifiers_format, $formatted_date);.
    return $formatted_date;
  }

}
