<?php

namespace Drupal\controlled_access_terms\Plugin\search_api\processor;

use Drupal\controlled_access_terms\Plugin\search_api\processor\Property\TypedRelationFilteredProperty;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
 *   locked = false,
 *   hidden = false,
 * )
 */
class TypedRelationFiltered extends ProcessorPluginBase {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The Entity Type Manager.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    EntityTypeManager $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL): array {
    $properties = [];

    if (!$datasource || !$datasource->getEntityTypeId()) {
      return $properties;
    }

    $entity_type = $datasource->getEntityTypeId();

    // Get all configured typed relation fields.
    $fields = $this->entityTypeManager->getStorage('field_config')->loadByProperties([
      'entity_type' => $entity_type,
      'field_type' => 'typed_relation',
    ]);

    foreach ($fields as $field) {
      // Create a "filtered" option.
      $definition = [
        'label' => $this->t('@label (filtered by type) [@bundle]', [
          '@label' => $field->label(),
          '@bundle' => $field->getTargetBundle(),
        ]),
        'description' => $this->t('Typed relation field, filtered by type'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
        'settings' => [
          'options' => $field->getSetting('rel_types'),
          'bundle' => $field->getTargetBundle(),
          'base_field' => $field->getName(),
        ],
      ];
      $fieldname = 'typed_relation_filter__' . str_replace('.', '__', $field->id());
      $property = new TypedRelationFilteredProperty($definition);
      $property->setSetting('options', $field->getSetting('rel_types'));
      $properties[$fieldname] = $property;
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Skip if no Typed Relation Filtered search_api_fields are configured.
    $relevant_search_api_fields = [];
    $search_api_fields = $item->getFields(FALSE);
    foreach ($search_api_fields as $search_api_field) {
      if (substr($search_api_field->getPropertyPath(), 0, 23) == 'typed_relation_filter__') {
        $relevant_search_api_fields[] = $search_api_field;
      }
    }
    if (empty($search_api_fields)) {
      return;
    }
    // Cycle over any typed relation fields on the original item.
    $content_entity = $item->getOriginalObject()->getValue();

    foreach ($relevant_search_api_fields as $search_api_field) {
      $field_config = $search_api_field->getConfiguration();

      // Exit if we're on the wrong bundle or the field isn't set.
      if (($content_entity->bundle() != $field_config['bundle'])
        || !$content_entity->hasField($field_config['base_field'])) {
        return;
      }

      $vals = $content_entity->get($field_config['base_field'])->getValue();
      foreach ($vals as $element) {
        $rel_type = $element['rel_type'];
        if (in_array($rel_type, $field_config['rel_types'])) {
          $tid = $element['target_id'];
          $taxo_term = $this->entityTypeManager
            ->getStorage('taxonomy_term')
            ->load($tid);
          if ($taxo_term) {
            $taxo_name = $taxo_term->getName();
            $search_api_field->addValue($taxo_name);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requiresReindexing(array $old_settings = NULL, array $new_settings = NULL) {
    if ($new_settings != $old_settings) {
      return TRUE;
    }
    return FALSE;
  }

}
