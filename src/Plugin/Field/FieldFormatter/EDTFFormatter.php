<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

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
   * Month/Season to text map.
   *
   * @var array
   */
  private $MONTHS = [
    '01' => ['mmm' => 'Jan', 'mmmm' => 'January'],
    '02' => ['mmm' => 'Feb', 'mmmm' => 'February'],
    '03' => ['mmm' => 'Mar', 'mmmm' => 'March'],
    '04' => ['mmm' => 'Apr', 'mmmm' => 'April'],
    '05' => ['mmm' => 'May', 'mmmm' => 'May'],
    '06' => ['mmm' => 'Jun', 'mmmm' => 'June'],
    '07' => ['mmm' => 'Jul', 'mmmm' => 'July'],
    '08' => ['mmm' => 'Aug', 'mmmm' => 'August'],
    '09' => ['mmm' => 'Sep', 'mmmm' => 'September'],
    '10' => ['mmm' => 'Oct', 'mmmm' => 'October'],
    '11' => ['mmm' => 'Nov', 'mmmm' => 'November'],
    '12' => ['mmm' => 'Dec', 'mmmm' => 'December'],
    '21' => ['mmm' => 'Spr', 'mmmm' => 'Spring'],
    '22' => ['mmm' => 'Sum', 'mmmm' => 'Summer'],
    '23' => ['mmm' => 'Aut', 'mmmm' => 'Autumn'],
    '24' => ['mmm' => 'Win', 'mmmm' => 'Winter'],
    '25' => ['mmm' => 'Spr', 'mmmm' => 'Spring - Northern Hemisphere'],
    '26' => ['mmm' => 'Sum', 'mmmm' => 'Summer - Northern Hemisphere'],
    '27' => ['mmm' => 'Aut', 'mmmm' => 'Autumn - Northern Hemisphere'],
    '28' => ['mmm' => 'Win', 'mmmm' => 'Winter - Northern Hemisphere'],
    '29' => ['mmm' => 'Spr', 'mmmm' => 'Spring - Southern Hemisphere'],
    '30' => ['mmm' => 'Sum', 'mmmm' => 'Summer - Southern Hemisphere'],
    '31' => ['mmm' => 'Aut', 'mmmm' => 'Autumn - Southern Hemisphere'],
    '32' => ['mmm' => 'Win', 'mmmm' => 'Winter - Southern Hemisphere'],
    '33' => ['mmm' => 'Q1', 'mmmm' => 'Quarter 1'],
    '34' => ['mmm' => 'Q2', 'mmmm' => 'Quarter 2'],
    '35' => ['mmm' => 'Q3', 'mmmm' => 'Quarter 3'],
    '36' => ['mmm' => 'Q4', 'mmmm' => 'Quarter 4'],
    // I'm making up the rest of these abbreviations
    // because I can't find standardized ones.
    '37' => ['mmm' => 'Quad1', 'mmmm' => 'Quadrimester 1'],
    '38' => ['mmm' => 'Quad2', 'mmmm' => 'Quadrimester 2'],
    '39' => ['mmm' => 'Quad3', 'mmmm' => 'Quadrimester 3'],
    '40' => ['mmm' => 'Sem1', 'mmmm' => 'Semestral 1'],
    '41' => ['mmm' => 'Sem2', 'mmmm' => 'Semestral 2'],
  ];

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
    $settings = $this->getSettings();
    $cleaned_datetime = $edtf_text;
    // TODO: Time?
    $qualifiers_format = '%s';
    // Uncertainty.
    // TODO: Group Qualification
    if (!(strpos($edtf_text, '~') === FALSE)) {
      $qualifiers_format = t('approximately');
      $qualifiers_format .= ' %s';
    }
    if (!(strpos($edtf_text, '?') === FALSE)) {
      $qualifiers_format = '%s ';
      $qualifiers_format .= t('(uncertain)');
    }
    if (!(strpos($edtf_text, '%') === FALSE)) {
      $qualifiers_format = '%s ';
      $qualifiers_format .= t('(approximate and uncertain)');
    }
    $cleaned_datetime = str_replace(['?', '~', '%'], '', $cleaned_datetime);

    list($year, $month, $day) = explode('-', $cleaned_datetime, 3);

    // Which unspecified, if any?
    $which_unspecified = '';
    if (!(strpos($year, 'XX') === FALSE)) {
      $which_unspecified = t('decade');
    }
    if (!(strpos($year, 'X') === FALSE)) {
      $which_unspecified = t('year');
    }
    if (!(strpos($month, 'X') === FALSE)) {
      $which_unspecified = t('month');
      // No partial months.
      $month = '';
    }
    if (!(strpos($day, 'X') === FALSE)) {
      $which_unspecified = t('day');
      // No partial days.
      $day = '';
    }
    // Add unspecified formatting if needed.
    if (!empty($which_unspecified)) {
      $qualifiers_format = t('an unspecified @part in', ['@part' => $which_unspecified]) . ' ' . $qualifiers_format;
    }

    // Clean-up unspecified year/decade.
    if (!(strpos($year, 'X') === FALSE)) {
      $year = str_replace('X', '0', $year);
      $year = t("the @year's", ['@year' => $year]);
    }

    // Format the month.
    if (!empty($month)) {
      // IF 'mm', do nothing, it is already in this format.
      if ($settings['month_format'] === 'mmm' || $settings['month_format'] === 'mmmm') {
        $month = $this->MONTHS[$month][$settings['month_format']];
      }
      if ($settings['month_format'] === 'm') {
        $month = ltrim($month, ' 0');
      }
    }

    // Format the day.
    if (!empty($day)) {
      if ($settings['day_format'] === 'd') {
        $day = ltrim($day, ' 0');
      }
    }

    // Put the parts back together
    // Big Endian by default.
    $parts_in_order = [$year, $month, $day];

    if ($settings['date_order'] === 'little_endian') {
      $parts_in_order = [$day, $month, $year];
    }
    elseif ($settings['date_order'] === 'middle_endian') {
      $parts_in_order = [$month, $day, $year];
    } // Big Endian by default

    if ($settings['date_order'] === 'middle_endian' && !preg_match('/\d/', $month) && !empty(array_filter([$month, $day]))) {
      $cleaned_datetime = "$month $day, $year";
    }
    else {
      $cleaned_datetime = implode($this->DELIMITERS[$settings['date_separator']], array_filter($parts_in_order));
    }

    return sprintf($qualifiers_format, $cleaned_datetime);
  }

}
