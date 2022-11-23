<?php

namespace Drupal\controlled_access_terms\Plugin\search_api\processor;

use Drupal\controlled_access_terms\Plugin\search_api\processor\Property\TypedRelationFilteredProperty;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Adds filterable fields for each Typed Relation field.
 *
 * @SearchApiProcessor(
 *   id = "typed_relation_filtered",
 *   label = @Translation("Typed Relation, filtered by type"),
 *   description = @Translation("Filter Typed Relation fields by type"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = false,
 * )
 */
class TypedRelationFiltered extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL): array {
    # Get all configured typed relation fields.
    $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties(['field_type' => 'typed_relation']);
    $properties = [];
    foreach ($fields as $field) {
      // Set a filtered field.
      $definition = [
        'label' => $this->t('@label (filtered by type) [@bundle]', [
          '@label' => $field->label(),
          '@bundle' => $field->getTargetBundle(),
        ]),
        'description' => $this->t('Typed relation field, filtered by type'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'settings' => ['options' => $field->getSetting('rel_types')],
      ];
      $fieldname = 'typed_relation_filter__' . str_replace('.','__', $field->id());
      $properties[$fieldname] = new TypedRelationFilteredProperty($definition);
      $properties[$fieldname]->setSetting('options',$field->getSetting('rel_types') );
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Skip if no Typed Relation Filtered search_api_fields are configured.
    $skip = TRUE;
    $search_api_fields = $item->getFields(FALSE);
    foreach ($search_api_fields as $field) {
      if (substr($field->getPropertyPath(), 0, 23) == 'typed_relation_filter__') {
        $skip = FALSE;
      }
    }
    if ($skip) {
      return;
    }
    // Cycle over any typed relation fields on the original item.
    $node = $item->getOriginalObject()->getValue();
    $field_defs = $node->getFieldDefinitions();
    foreach ($field_defs as $field) {
      if ($field->getType() == 'typed_relation') {
        $field_name = $field->getName();
        if (!$node->get($field_name)->isEmpty()) {

          // See if this field is being indexed.
          $property_path = 'typed_relation_filter__' . str_replace('.','__', $field->id());
          $search_api_fields = $this->getFieldsHelper()
            ->filterForPropertyPath($search_api_fields, NULL, $property_path);

          foreach ($search_api_fields as $search_api_field) {

            // Load field values.
            $vals = $node->$field_name->getValue();
            foreach ($vals as $element) {
              $rel_type = $element['rel_type'];
              if (in_array($rel_type, $search_api_field->getConfiguration()['rel_types'])) {
                $tid = $element['target_id'];
                $taxo_term = \Drupal::entityTypeManager()
                  ->getStorage('taxonomy_term')
                  ->load($tid);
                if ($taxo_term) {
                  $taxo_name = $taxo_term->name->value;
                  $search_api_field->addValue($taxo_name);
                }
              }
            }
          }
        }
      }
    }
  }

}
