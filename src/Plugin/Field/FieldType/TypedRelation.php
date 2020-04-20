<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Implements a Typed Relation field.
 *
 * @FieldType(
 *   id = "typed_relation",
 *   label = @Translation("Typed Relation"),
 *   module = "controlled_access_terms",
 *   description = @Translation("Implements a typed relation field"),
 *   default_formatter = "typed_relation_default",
 *   default_widget = "typed_relation_default",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class TypedRelation extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['rel_type'] = [
      'type' => 'text',
      'size' => 'tiny',
      'not null' => TRUE,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['rel_type'] = DataDefinition::create('string')
      ->setLabel(t('Type'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $parentEmpty = parent::isEmpty();

    // All must have a value.
    if ($this->rel_type !== NULL &&
         !empty($this->rel_type) &&
         !($parentEmpty)
       ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return ['rel_types' => []] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);

    $element['rel_types'] = [
      '#type' => 'textarea',
      '#title' => t('Available Relations'),
      '#default_value' => $this->encodeTextSettingsField($this->getSetting('rel_types')),
      '#element_validate' => [[get_class($this), 'validateValues']],
      '#required' => TRUE,
      '#min' => 1,
      '#description' => '<p>' . t('Enter one value per line, in the format key|label.') .
      '<br/>' . t('The key is the stored value. The label will be used in displayed values and edit forms.') .
      '<br/>' . t("Keys may not contain dots ('.'). They will be removed if used.") .
      '<br/>' . t('The label is optional: if a line contains a single string, it will be used as key and label.') .
      '</p>',
    ];

    return $element;
  }

  /**
   * Convenience method allowing the Formatter to get the rel_types.
   *
   * @return array
   *   The array of relation types
   */
  public function getRelTypes() {
    return $this->getSetting('rel_types');
  }

  /**
   * Encodes pipe-delimited key/value pairs.
   *
   * @param array $settings
   *   The array of key/value pairs to encode.
   *
   * @return string
   *   The string of encoded key/value pairs.
   */
  protected function encodeTextSettingsField(array $settings) {
    $output = '';
    foreach ($settings as $key => $value) {
      $output .= "$key|$value\n";
    }
    return $output;
  }

  /**
   * Extracts pipe-delimited key/value pairs.
   *
   * @param string $string
   *   The raw string to extract values from.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::extractAllowedValues()
   */
  protected static function extractPipedValues($string) {
    $values = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $position => $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        // Also remove dots in keys (which aren't permitted.)
        $key = str_replace('.', '', trim($matches[1]));
        $value = trim($matches[2]);
      }
      // Otherwise use the value as key and value.
      else {
        $key = $value = $text;
      }

      $values[$key] = $value;
    }

    return $values;
  }

  /**
   * Callback for settings form.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateValues(array $element, FormStateInterface $form_state) {
    $values = static::extractPipedValues($element['#value']);

    if (!is_array($values)) {
      $form_state->setError($element, t('Allowed values list: invalid input.'));
    }
    else {
      // We may want to validate key values in the future...
      $form_state->setValueForElement($element, $values);
    }
  }

}
