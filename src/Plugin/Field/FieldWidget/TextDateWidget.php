<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldWidget;

use Datetime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'text_date' widget.
 *
 * @FieldWidget(
 *   id = "text_date",
 *   label = @Translation("Date as Text"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class TextDateWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Assign a default date format of.
      'date_format' => 'Y-m-d',
      'strict_dates' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PHP DateTime Format String.'),
      '#description' => $this->t('See <a href="@phpdate" target="_blank">PHP DateTime Documentation</a> for details.', ['@phpdate' => 'http://php.net/manual/en/datetime.createfromformat.php']),
      '#default_value' => $this->getSetting('date_format'),
    ];
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

    $summary[] = t('Date Format: @format', ['@format' => $this->getSetting('date_format')]);

    if ($this->getSetting('strict_dates')) {
      $summary[] = t('Strict dates enabled');
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
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    $date_format = $this->getSetting('date_format');
    $date = DateTime::createFromFormat($date_format, $value);
    if (!$date) {
      $form_state->setError($element, t("Date must match the pattern @format",
        ['@format' => $date_format]));
    }
    $errors = DateTime::getLastErrors();
    if ($this->getSetting('strict_dates') && !empty($errors['warning_count'])) {
      $form_state->setError($element, t('Strictly speaking, the date "@value" is invalid!',
        ['@value' => $value]));
    }
  }

}
