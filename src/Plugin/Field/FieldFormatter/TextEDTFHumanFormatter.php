<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

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
class TextEDTFHumanFormatter extends FormatterBase {

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
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
    // ISO 8601 bias.
      'date_separator' => 'dash',
    // ISO 8601 bias.
      'date_order' => 'big_endian',
    // ISO 8601 bias.
      'month_format' => 'mm',
    // ISO 8601 bias.
      'day_format' => 'dd',
    // Northern bias, sorry.
      'season_hemisphere' => 'north',
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
    $form['season_hemisphere'] = [
      '#title' => t('Hemisphere Seasons'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('season_hemisphere'),
      '#description' => t("Seasons don't have digit months so we map them 
                          to their respective equinox and solstice months.
                          Select a hemisphere to use for the mapping."),
      '#options' => [
        'north' => t('Northern Hemisphere'),
        'south' => t('Southern Hemisphere'),
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
      list($begin, $end) = explode('/', $item->value);

      $formatted_begin = $this->formatDate($begin);

      // End either empty or valid extended interval values (5.2.3.)
      if (empty($end)) {
        $element[$delta] = ['#markup' => $formatted_begin];
      }
      elseif ($end === 'unknown' || $end === 'open') {
        $element[$delta] = [
          '#markup' => t('@begin to @end', [
            '@begin' => $formatted_begin,
            '@end' => $end,
          ]),
        ];
      }
      else {
        $formatted_end = $this->formatDate($end);
        $element[$delta] = [
          '#markup' => t('@begin to @end', [
            '@begin' => $formatted_begin,
            '@end' => $formatted_end,
          ]),
        ];
      }

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
    $qualifiers_format = '';
    // Uncertainty.
    if (!(strpos($edtf_text, '~') === FALSE)) {
      $qualifiers_format = t('approximately');
      $qualifiers_format .= ' %s';
    }
    if (!(strpos($edtf_text, '?') === FALSE)) {
      $qualifiers_format = '%s ';
      $qualifiers_format .= t('(uncertain)');
    }
    $cleaned_datetime = str_replace(['?', '~'], '', $cleaned_datetime);

    list($year, $month, $day) = explode('-', $cleaned_datetime, 3);

    // Which unspecified, if any?
    $which_unspecified = '';
    if (!(strpos($year, 'uu') === FALSE)) {
      $which_unspecified = t('decade');
    }
    if (!(strpos($year, 'u') === FALSE)) {
      $which_unspecified = t('year');
    }
    if (!(strpos($month, 'u') === FALSE)) {
      $which_unspecified = t('month');
      // No partial months.
      $month = '';
    }
    if (!(strpos($day, 'u') === FALSE)) {
      $which_unspecified = t('day');
      // No partial days.
      $day = '';
    }
    // Add unspecified formatting if needed.
    if (!empty($which_unspecified)) {
      $qualifiers_format = t('an unspecified @part in', ['@part' => $which_unspecified]) . ' ' . $qualifiers_format;
    }

    // Clean-up unspecified year/decade.
    if (!(strpos($year, 'u') === FALSE)) {
      $year = str_replace('u', '0', $year);
      $year = t("the @year's", ['@year' => $year]);
    }

    // Format the month.
    if (!empty($month)) {
      // IF 'mm', do nothing, it is already in this format.
      if ($settings['month_format'] === 'mmm' || $settings['month_format'] === 'mmmm') {
        $month = $this->MONTHS[$month][$settings['month_format']];
      }
      // Digit Seasons.
      elseif (in_array($month, ['21', '22', '23', '24'])) {
        $season_mapping = ($settings['season_hemisphere'] === 'north' ? $this->seasonMapNorth : $this->seasonMapSouth);
        $month = $season_mapping[$month];
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
