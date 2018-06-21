<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

use \Datetime;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TextEDTFHumanFormatter'.
 *
 * @FieldFormatter(
 *   id = "text_edtf_human",
 *   label = @Translation("EDTF for Humans"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class TextEDTFHumanFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
   public static function defaultSettings() {
    return [
      // assign a default date format of
      'date_format' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PHP DateTime Format String (http://php.net/manual/en/datetime.createfromformat.php); e.g. Y-m-d'),
      '#default_value' => $this->getSetting('date_format'),
      '#description' => $this->t(
        'If a date format is used then the earliest date of that format '.
        'will be used. <br />E.g. using the format \'Y-m-d\' will display '.
        'for a value of \'2018\' will display \'2018-01-01\'.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if(!empty($this->getSetting('date_format'))){
      $summary[] = t('Date Format: @format',
                      array('@format' => $this->getSetting('date_format')));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
   public function viewElements(FieldItemListInterface $items, $langcode) {
     $element = array();
     $entity = $items
       ->getEntity();
     $settings = $this
       ->getSettings();

     foreach ($items as $delta => $item) {
       // Interval
       list($begin, $end) = explode('/',$item->value);

       $formatted_begin = $this->formatDate($begin);

       // end either empty or valid extended interval values (5.2.3.)
       if(empty($end)){
         $element[$delta] = ['#markup' => $formatted_begin];
       } elseif ($end === 'unknown' || $end === 'open') {
         $element[$delta] = ['#markup' => $formatted_begin .
                                          t(' to ') .
                                          t($end)];
       } else {
         $formatted_end = $this->formatDate($end);
         $element[$delta] = ['#markup' => $formatted_begin .
                                          t(' to ') .
                                          $formatted_end];
       }

     }
     return $element;
   }

   protected function formatDate($edtf_text){
     $settings = $this->getSettings();
     $cleaned_datetime = $edtf_text;
     // TODO: Time?

     // TODO: Uncertainty

     // Format date TODO: time support
     // TODO: bad formatting gives no warning.
     if(!empty($settings['date_format'])){
       // Set earliest date
       list($year, $month, $day) = explode('-',$cleaned_datetime,3);
       $month = (empty($month) ? '01' : $month);
       $day = (empty($day) ? '01' : $day);

       // Parse the date
       // $datetime_obj = DateTime::createFromFormat('Y-m-d\TH:i:s',
       $datetime_obj = DateTime::createFromFormat('!'.'Y-m-d',
                                                  "$year-$month-$day");
       $errors = DateTime::getLastErrors();
       if( !$datetime_obj || !empty($errors['warning_count']) ) {
         drupal_set_message(
           t('Either the date or the date format could not be used: ') .
           "$year-$month-$day" .
           ' ' . t('and') . ' ' .
           'Y-m-d', 'warning');
          return $cleaned_datetime;
       } else { // Time to format
         $formatted_date = $datetime_obj->format($settings['date_format']);
         if($formatted_date){
           return $formatted_date;
         } else {
           drupal_set_message(
             t('Either the date or the date format could not be used: ') .
             "$year-$month-$day" .
             ' ' . t('and') . ' ' .
             $settings['date_format'], 'warning');
           return $cleaned_datetime;
         }
       }
     }


     return $edtf_text;
   }

}

 ?>
