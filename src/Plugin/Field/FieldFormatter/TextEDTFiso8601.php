<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

use \Datetime;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TextEDTFiso8601'.
 * Only supports EDTF through level 1.
 *
 * Uses first interval. An option to select which interval
 * could come in the future.
 *
 * Approximation and uncertainty is dropped.
 * Only supports earlist date in range of unspecified dates.
 * Max dates could be added in future versions.
 *
 * @FieldFormatter(
 *   id = "text_edtf_iso8601",
 *   label = @Translation("EDTF (L1) ISO 8601"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class TextEDTFiso8601 extends FormatterBase {

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
      'season_hemisphere' => 'north', // Northern bias, sorry.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['season_hemisphere'] = array(
      '#title' => t('Hemisphere Seasons'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('season_hemisphere'),
      '#description' => t('Seasons aren\'t currently supported by iso 8601. ' .
                          'We map them to their respective equinox and ' .
                          'solstice months. Select a hemisphere to use for ' .
                          'the mapping.'),
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
    if($this->getSetting('strict_dates') === 'south'){
      $summary[] = t('Seasons mapped to the southern hemisphere.');
    }
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
      // End is currently ignored.


      // Strip approximations/uncertainty.
      $begin = str_replace(array('?','~'),'',$begin);

      // Replace unspecified.
      $begin = str_replace( '-uu', '-01', $begin ); // Month/day
      $begin = str_replace( 'u', '0', $begin ); // Zero-Year in decade/century

      drupal_set_message("Date before mapping: $begin");
      // Seasons map
      list($year, $month, $day) = explode('-',$begin,3);
      if(in_array($month, ['21','22','23','24'])){ //Digit Seasons
        $season_map = ($settings['season_hemisphere'] === 'north' ? $this->SEASON_MAP_NORTH : $this->SEASON_MAP_SOUTH);
        $month = $season_mapping[$month];
        $begin = implode( '-', array_filter( [$year,$month,$day] ) );
      }

      $element[$delta] = ['#markup' => $begin];
    }
    return $element;
  }

}

 ?>
