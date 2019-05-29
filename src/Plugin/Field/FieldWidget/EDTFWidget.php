<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\controlled_access_terms\EDTFUtils;

/**
 * Plugin implementation of the 'edtf' widget.
 *
 * Validates text values for compliance with EDTF (2018).
 * https://www.loc.gov/standards/datetime/edtf.html.
 *
 * @FieldWidget(
 *   id = "edtf_default",
 *   label = @Translation("Default EDTF widget"),
 *   field_types = {
 *     "edtf"
 *   }
 * )
 */
class EDTFWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'strict_dates' => FALSE,
      'intervals' => TRUE,
      'sets' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $description_string = $this->t(
        'Most level 1 and 2 features are not supported with strict date checking.'
    );
    $description_string .= ' <br /> ';
    $description_string .= $this->t(
      'Uncertain/Approximate dates will have their markers removed before
        checking. (For example, "1984?", "1984~", and "1984%" will be checked as "1984".)'
    );
    $element = parent::settingsForm($form, $form_state);
    $element['description'] = [
      '#type' => 'markup',
      '#prefix' => '<div>',
      '#suffix' => '</div>',
      '#markup' => $this->t('See <a href="@locedtf" target="_blank">Library of Congress EDTF Specification</a> for details on formatting options.', ['@locedtf' => 'https://www.loc.gov/standards/datetime/edtf.html']),
    ];
    $element['strict_dates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ensure provided date values are valid.'),
      '#description' => $description_string,
      '#default_value' => $this->getSetting('strict_dates'),
    ];
    $element['intervals'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Permit date intervals.'),
      '#default_value' => $this->getSetting('intervals'),
    ];
    $element['sets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Permit date sets. (Not recommended; make the field repeatable instead.)'),
      '#default_value' => $this->getSetting('sets'),
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
    if ($this->getSetting('sets')) {
      $summary[] = t('Date sets permitted');
    }
    else {
      $summary[] = t('Date sets are not permitted');
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
    $errors = EDTFUtils::validate($value, $this->getSetting('intervals'), $this->getSetting('sets'), $this->getSetting('strict_dates'));
    if (!empty($errors)) {
      $form_state->setError($element, implode("\n", $errors));
    }
  }

}
