<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

use \Datetime;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TextEDTFHumanFormatter'.
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

  private $MONTHS = array(
    '01' => array('mmm'=>'Jan', 'mmmm'=>'January'),
    '02' => array('mmm'=>'Feb', 'mmmm'=>'February'),
    '03' => array('mmm'=>'Mar', 'mmmm'=>'March'),
    '04' => array('mmm'=>'Apr', 'mmmm'=>'April'),
    '05' => array('mmm'=>'May', 'mmmm'=>'May'),
    '06' => array('mmm'=>'Jun', 'mmmm'=>'June'),
    '07' => array('mmm'=>'Jul', 'mmmm'=>'July'),
    '08' => array('mmm'=>'Aug', 'mmmm'=>'August'),
    '09' => array('mmm'=>'Sep', 'mmmm'=>'September'),
    '10' => array('mmm'=>'Oct', 'mmmm'=>'October'),
    '11' => array('mmm'=>'Nov', 'mmmm'=>'November'),
    '12' => array('mmm'=>'Dec', 'mmmm'=>'December'),
    '21' => array('mmm'=>'Spr', 'mmmm'=>'Spring'),
    '22' => array('mmm'=>'Sum', 'mmmm'=>'Summer'),
    '23' => array('mmm'=>'Aut', 'mmmm'=>'Autumn'),
    '24' => array('mmm'=>'Win', 'mmmm'=>'Winter'),
  );

  private $DELIMITERS = array(
    'dash'   => '-',
    'stroke' => '/',
    'period' => '.',
    'space'  => ' ',
  );

  private $SEASON_MAP_NORTH = [
    '21' => '03', // Spring => March
    '22' => '06', // Summer => June
    '23' => '09', // Autumn => September
    '24' => '12', // Winter => December
  ];

  private $SEASON_MAP_SOUTH = [
    '21' => '03', // Spring => September
    '22' => '06', // Summer => December
    '23' => '09', // Autumn => March
    '24' => '12', // Winter => June
  ];

  /**
   * {@inheritdoc}
   */
   public static function defaultSettings() {
    return [
      'date_separator' => 'dash',   // ISO 8601 bias
      'date_order' => 'big_endian', // ISO 8601 bias
      'month_format' => 'mm',       // ISO 8601 bias
      'day_format' => 'dd',         // ISO 8601 bias
      'season_hemisphere' => 'north', // Northern bias, sorry.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['date_separator'] = array(
      '#title' => t('Date Separator'),
      '#type' => 'select',
      '#description' => "Select the separator between date elements.",
      '#default_value' => $this->getSetting('date_separator'),
      '#options' => array(
                  'dash' => t('Dash') . ' \'-\'',
                  'stroke' => t('Stroke') . ' \'/\'',
                  'period' => t('Period') . ' \'.\'',
                  'space' => t('Space') . ' \' \'',
               ),
    );
    $form['date_order'] = array(
      '#title' => t('Date Order'),
      '#type' => 'select',
      '#description' => "Select the separator between date elements.",
      '#default_value' => $this->getSetting('date_order'),
      '#options' => array(
                  'big_endian' => t('Big-endian (year, month, day)'),
                  'little_endian' => t('Little-endian (day, month, year)'),
                  'middle_endian' => t('Middle-endian (month, day, year)'),
               ),
    );
    $form['month_format'] = array(
      '#title' => t('Month Format'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('month_format'),
      '#options' => array(
                  'mm' => t('two-digit month, e.g. 04'),
                  'm' => t('one-digit month for months below 10, e.g. 4'),
                  'mmm' => t('three-letter abbreviation for month, ') . t('Apr'),
                  'mmmm' => t('month spelled out in full, e.g. ') . t('April'),
               ),
    );
    $form['day_format'] = array(
      '#title' => t('Day Format'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('day_format'),
      '#options' => array(
                  'dd' => t('two-digit day of the month, e.g. 02'),
                  'd' => t('one-digit day of the month for days below 10, e.g. 2'),
               ),
    );
    $form['season_hemisphere'] = array(
      '#title' => t('Hemisphere Seasons'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('season_hemisphere'),
      '#description' => t('Seasons don\'t have digit months so we map them ' .
                          'to their respective equinox and solstice months. ' .
                          'Select a hemisphere to use for the mapping.'),
      '#options' => array(
                  'north' => t('Northern Hemisphere'),
                  'south' => t('Southern Hemisphere'),
               ),
    );
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
     $element = array();
     $entity = $items->getEntity();
     $settings = $this->getSettings();

     foreach ($items as $delta => $item) {
       // Interval
       list($begin, $end) = explode('/',$item->value);

       $formatted_begin = $this->formatDate($begin);

       // end either empty or valid extended interval values (5.2.3.)
       if(empty($end)){
         $element[$delta] = ['#markup' => $formatted_begin];
       } elseif ($end === 'unknown' || $end === 'open') {
         $element[$delta] = ['#markup' => $formatted_begin . ' ' .
                                          t('to') . ' ' .
                                          t($end)];
       } else {
         $formatted_end = $this->formatDate($end);
         $element[$delta] = ['#markup' => $formatted_begin . ' ' .
                                          t('to') . ' ' .
                                          $formatted_end];
       }

     }
     return $element;
   }

   protected function formatDate($edtf_text){
     $settings = $this->getSettings();
     $cleaned_datetime = $edtf_text;
     // TODO: Time?

     // Uncertainty
     $qualifiers_format = "%s";
     if(!(strpos($edtf_text, '~') === false)){
       $qualifiers_format = t('approximately') . ' ' . $qualifiers_format;
     }
     if(!(strpos($edtf_text, '?') === false)){
       $qualifiers_format .= ' (' . t('uncertain'.')');
     }
     $cleaned_datetime = str_replace(array('?','~'),'',$cleaned_datetime);

     list($year, $month, $day) = explode('-',$cleaned_datetime,3);

     // Which unspecified, if any?
     $which_unspecified = '';
     if (!(strpos($year, 'uu') === false)) {
       $which_unspecified = t('decade');
     }
     if (!(strpos($year, 'u') === false)) {
       $which_unspecified = t('year');
     }
     if (!(strpos($month, 'u') === false)) {
       $which_unspecified = t('month');
       $month = ''; // No partial months
     }
     if(!(strpos($day, 'u') === false)){
       $which_unspecified = t('day');
       $day = ''; // No partial days
     }
     // Add unspecified formatting if needed
     if(!empty($which_unspecified)){
       $qualifiers_format = t('an unspecified @part in', ['@part' => $which_unspecified]) . ' ' . $qualifiers_format;
     }

     // Clean-up unspecified year/decade
     if (!(strpos($year,'u') === false)){
       $year = str_replace( 'u', '0', $year );
       $year = t('the @year\'s', ['@year' => $year ]);
     }


     // Format the month
     if( !empty($month) ){
       // IF 'mm', do nothing, it is already in this format.
       if ($settings['month_format'] === 'mmm' || $settings['month_format'] === 'mmmm' ){
         $month = t($this->MONTHS[$month][$settings['month_format']]);
       } elseif(in_array($month, ['21','22','23','24'])){ //Digit Seasons
         $season_map = ($settings['season_hemisphere'] === 'north' ? $this->SEASON_MAP_NORTH : $this->SEASON_MAP_SOUTH);
         $month = $season_mapping[$month];
       }

       if($settings['month_format'] === 'm'){
         $month = ltrim($month,' 0');
       }
     }

     // Format the day
     if( !empty($day) ){
       if($settings['day_format'] === 'd'){
         $day = ltrim($day,' 0');
       }
     }

     // Put the parts back together
     $parts_in_order = [$year, $month, $day]; // Big Endian by default

     if($settings['date_order'] === 'little_endian'){
       $parts_in_order = [$day, $month, $year];
     } elseif ($settings['date_order'] === 'middle_endian') {
       $parts_in_order = [$month, $day, $year];
     } // Big Endian by default

     $formatted_date = '';
     if ($settings['date_order'] === 'middle_endian' && !preg_match('/\d/',$month) && !empty(array_filter([$month,$day]))) {
         $cleaned_datetime = "$month $day, $year";
     } else {
       $cleaned_datetime = implode($this->DELIMITERS[$settings['date_separator']], array_filter( $parts_in_order ));
     }

     return sprintf($qualifiers_format, $cleaned_datetime);
   }

}

 ?>
