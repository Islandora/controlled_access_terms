<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldType;

use Drupal\link\LinkItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Plugin implementation of the 'authority_link' field type.
 *
 * @FieldType(
 *   id = "authority_link",
 *   label = @Translation("Authority Link"),
 *   description = @Translation("Stores a URL string, an authority source dropdown, an optional varchar link text, and optional blob of attributes to assemble a link."),
 *   default_widget = "authority_link_default",
 *   default_formatter = "authority_formatter_default",
 *   constraints = {
 *     "LinkType" = {},
 *     "LinkAccess" = {},
 *     "LinkExternalProtocols" = {},
 *     "LinkNotExistingInternal" = {}
 *   }
 * )
 */
class AuthorityLink extends LinkItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['source'] = [
      'type' => 'text',
      'size' => 'tiny',
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['source'] = DataDefinition::create('string')->setLabel(t('Source Authority'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['authority_sources'] = ['other' => t('Other')];
    $settings['link_type'] = LinkItemInterface::LINK_EXTERNAL;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['authority_sources'] = [
      '#type' => 'textarea',
      '#title' => t('Authority Sources'),
      '#default_value' => $this->encodeTextSettingsField($this->getSetting('authority_sources')),
      '#element_validate' => [[get_class($this), 'validateValues']],
      '#required' => TRUE,
      '#min' => 1,
      '#description' => '<p>' . t('Enter one value per line, in the format key|label.') .
      '<br/>' . t('The key is the stored value. The label will be used in displayed values and edit forms.') .
      '<br/>' . t('The label is optional: if a line contains a single string, it will be used as key and label.') .
      '</p>',
    ];

    return $element;
  }

  /**
   * Get the authority sources.
   *
   * @return mixed
   *   The authority sources.
   */
  public function getSources() {
    return $this->getSetting('authority_sources');
  }

  /**
   * Encode text settings as key|value.
   *
   * @param array $settings
   *   The settings to encode.
   *
   * @return string
   *   The multi-line text of key|value pairs.
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
        $key = trim($matches[1]);
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
   * An #element_validate callback function.
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
      // @codingStandardsIgnoreStart
      /*
      foreach ($values as $key => $value) {
        if ($error = static::validateAllowedValue($key)) {
          $form_state->setError($element, $error);
          break;
        }
      }
      */
      // @codingStandardsIgnoreStop
      $form_state->setValueForElement($element, $values);
    }
  }

}
