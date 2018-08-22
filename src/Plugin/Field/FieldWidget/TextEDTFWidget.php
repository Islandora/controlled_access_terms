<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldWidget;

use Datetime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

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
class TextEDTFWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'strict_dates' => FALSE,
      'intervals' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['strict_dates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ensure provided date values are valid.'),
      '#description' => $this->t(
        'Negative dates, and the level 1 features unspecified dates, 
        extended years, and seasons
        are not supported with strict date checking.<br />
        Uncertain/Approximate dates will have their markers removed before
        checking. (For example, "1984~?" will be checked as "1984".)'),
      '#default_value' => $this->getSetting('strict_dates'),
    ];
    $element['intervals'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Permit date intervals.'),
      '#default_value' => $this->getSetting('intervals'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($this->getSetting('strict_dates')) {
      $summary[] = t('Strict dates enabled');
    }
    if ($this->getSetting('intervals')) {
      $summary[] = t('Date intervals permitted');
    }
    else {
      $summary[] = t('Date intervals are not permitted');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this
        ->getSetting('placeholder'),
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];
    return $element;
  }

  /**
   * Validate date format compliance.
   */
  public function validate($element, FormStateInterface $form_state) {
    // Accept a blank (empty) value.
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }

    // Intervals.
    if ($this->getSetting('intervals')) {
      if (strpos($value, 'T') !== FALSE) {
        $form_state->setError($element, t("Date intervals cannot include times."));
      }

      list($begin, $end) = explode('/', $value);
      // Begin.
      $error_message = $this->dateValidation($begin);
      if ($error_message) {
        $form_state->setError($element, $error_message);
      }
      // End either empty or valid extended interval values (5.2.3.)
      if (empty($end) || $end === 'unknown' || $end === 'open') {
        return;
      }
      $error_message = $this->dateValidation($end);
      if ($error_message) {
        $form_state->setError($element, $error_message);
      }
    }
    else {
      $error_message = $this->dateValidation($value);
      if ($error_message) {
        $form_state->setError($element, $error_message);
      }
    }
  }

  /**
   * Validate a date.
   *
   * @param string $datetime_str
   *   The datetime string.
   *
   * @return bool|string
   *   False if valid or a string explaining the reason for invalidation.
   */
  protected function dateValidation($datetime_str) {

    list($date, $time) = explode('T', $datetime_str);

    $date = trim($date);
    $extended_year = (strpos($date, 'y') === 0 ? TRUE : FALSE);
    if ($extended_year && $this->getSetting('strict_dates')) {
      return "Extended years (5.2.4.) are not supported with the 'strict dates' option enabled.";
    }
    // Uncertainty characters on the end are valid Level 1 features (5.2.1.),
    // pull them off to make checking the rest easier.
    $date = rtrim($date, '?~');

    // Negative year? That is fine, but remove it
    // and the extended year indicator before exploding the date.
    $date = ltrim($date, 'y-');

    // Now to check the parts.
    list($year, $month, $day) = explode('-', $date, 3);

    // Year.
    if (!preg_match('/^\d\d(\d\d|\du|uu)$/', $year) && !$extended_year) {
      return "The year '$year' is invalid. Please enter a four-digit year.";
    }
    elseif ($extended_year && !preg_match('/^\d{5,}$/', $year)) {
      return "Invalid extended year. Please enter at least a four-digit year.";
    }
    $strict_pattern = 'Y';

    // Month.
    if (!empty($month) && !preg_match('/^(\d\d|\du|uu)$/', $month)) {
      return "The month '$month' is invalid. Please enter a two-digit month.";
    }
    if (!empty($month)) {
      if (strpos($year, 'u') !== FALSE && strpos($month, 'u') === FALSE) {
        return "The month must either be blank or unspecified when the year is unspecified.";
      }
      $strict_pattern = 'Y-m';
    }

    // Day.
    if (!empty($day) && !preg_match('/^(\d\d|\du|uu)$/', $day)) {
      return "The day '$day' is invalid. Please enter a two-digit day.";
    }
    if (!empty($day)) {
      if (strpos($month, 'u') !== FALSE && strpos($day, 'u') === FALSE) {
        return "The day must either be blank or unspecified when the month is unspecified.";
      }
      $strict_pattern = 'Y-m-d';
    }

    // Time.
    if (strpos($datetime_str, 'T') !== FALSE && empty($time)) {
      return "Time not provided with time seperator (T).";
    }

    if ($time) {
      if (!preg_match('/^-?(\d{4})(-\d{2}){2}T\d{2}(:\d{2}){2}(Z|(\+|-)\d{2}:\d{2})?$/', $datetime_str, $matches)) {
        return "The date/time '$datetime_str' is invalid. See EDTF 1.0, 5.1.2.";
      }
      drupal_set_message(print_r($matches, TRUE));
      $strict_pattern = 'Y-m-d\TH:i:s';
      if (count($matches) > 4) {
        if ($matches[4] === 'Z') {
          $strict_pattern .= '\Z';
        }
        else {
          $strict_pattern .= 'P';
        }
      }
    }

    if ($this->getSetting('strict_dates')) {
      // Clean the date/time string to ensure it parses correctly.
      $cleaned_datetime = str_replace('u', '1', $datetime_str);
      $datetime_obj = DateTime::createFromFormat('!' . $strict_pattern, $cleaned_datetime);
      $errors = DateTime::getLastErrors();
      if (!$datetime_obj ||
          !empty($errors['warning_count']) ||
          // DateTime will create valid dates from Y-m without warning,
          // so validate we still have what it was given.
          !($cleaned_datetime === $datetime_obj->format($strict_pattern))
        ) {
        return "Strictly speaking, the date (and/or time) '$datetime_str' is invalid.";
      }

    }

    return FALSE;
  }

}
