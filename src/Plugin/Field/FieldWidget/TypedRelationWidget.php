<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the typed note widget.
 *
 * @FieldWidget(
 *   id = "typed_relation_default",
 *   label = @Translation("Typed Relation Widget"),
 *   field_types = {
 *     "typed_relation"
 *   }
 * )
 */
class TypedRelationWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);

    $item =& $items[$delta];
    $settings = $item->getFieldDefinition()->getSettings();

    $widget['rel_type'] = [
      '#title' => t('Relationship Type'),
      '#type' => 'select',
      '#options' => $settings['rel_types'],
      '#default_value' => isset($item->rel_type) ? $item->rel_type : '',
      '#weight' => -1,
    ];

    return $widget;
  }

}
