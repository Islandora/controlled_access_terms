<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldWidget;

use \Datetime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'text_edtf' widget.
 * (Partially) validates text values for compliance with EDTF 1.0
 * http://www.loc.gov/standards/datetime/pre-submission.html
 *
 * Starting with level 0. Will pursue level 1. Maybe level 2 later.
 *
 * @FieldWidget(
 *   id = "text_edtf",
 *   label = @Translation("Extended Date Time Format, Level 0"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class EDTFWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
   public static function defaultSettings() {
    return [
      'strict_dates' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['strict_dates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ensure date values are valid.'),
      '#default_value' => $this->getSetting('strict_dates'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if($this->getSetting('strict_dates')){
      $summary[] = t('Strict dates enabled');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this
        ->getSetting('placeholder'),
      '#element_validate' => array(
        array($this, 'validate')
      ),
    );
    return $element;
  }

  /**
  * Validate date format compliance
  */
  public function validate($element, FormStateInterface $form_state){
    // Accept a blank (empty) value
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }

    // TODO: Intervals
    if(strpos('/', $value) !== false){
      $form_state->setError($element, t("Date intervals are not currently supported."));
    }

    list($date, $time) = explode('T',$value);

    // Date
    // Negative year? That is fine, but remove it before exploding the date
    if(strpos('-',$value) == 0){
      $value = ltrim($value, '-';)
    }
    list($year, $month, $day) = explode('-',$date);

    if(!preg_match('/^\d\d\d\d$/', $year)){
      $form_state->setError($element, t("The year is invalid. Please enter a four-digit year."));
    }

    // TODO: Time
    if($time){
      $form_state->setError($element, t("Time values are not currently supported."));
    }

    // Date

    // Time
    // Split pieces & check each one for acceptable characters/ranges.
    // If specific dates are given, are they valid?
  }

}
