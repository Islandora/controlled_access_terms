<?php

namespace Drupal\controlled_access_terms\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines a "Typed relation by type" property.
 *
 * @see \Drupal\controlled_access_terms\Plugin\search_api\processor\TypedRelationFiltered
 */
class TypedRelationFilteredProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'rel_types' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $configuration = $field->getConfiguration();
    $form['rel_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Relations to include'),
      '#options' => $field->getDataDefinition()->getSetting('options'),
      '#multiple' => TRUE,
      '#default_value' => $configuration['rel_types'],
      '#required' => TRUE,
      '#size' => 16,
    ];
    return $form;
  }

}
