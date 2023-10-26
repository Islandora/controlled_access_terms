<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TypedRelationFormatter'.
 *
 * @FieldFormatter(
 *   id = "typed_relation_default",
 *   label = @Translation("Typed Relation Formatter"),
 *   field_types = {
 *     "typed_relation"
 *   }
 * )
 */
class TypedRelationFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'suppress_code' => FALSE,
      ] + parent::defaultSettings();
  }
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements = parent::settingsForm($form, $form_state);
    $elements['suppress_code'] = [
      '#title' => t('Hide code in () after label, if present'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('suppress_code'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->getSetting('suppress_code') ? t('Suppress code in (), if present') : t('Show code in (), if present');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {

      $rel_types = $item->getRelTypes();
      $rel_type = isset($rel_types[$item->rel_type]) ? $rel_types[$item->rel_type] : $item->rel_type;

      // Suppress code, e.g. change Author (aut) to Author.
      if ($this->getSetting('suppress_code') == TRUE) {
        $rel_type = preg_replace('/ \([^()]*\)/', '', $rel_type);
      }

      $elements[$delta]['#prefix'] = $rel_type . ': ';
    }

    return $elements;
  }

}
