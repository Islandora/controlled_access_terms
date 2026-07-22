<?php

declare(strict_types=1);

namespace Drupal\controlled_access_terms\EventSubscriber;

use Drupal\controlled_access_terms\Plugin\Field\FieldType\TypedRelation;
use Drupal\Core\DefaultContent\ExportMetadata;
use Drupal\Core\DefaultContent\PreExportEvent;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Integrates with core's default content system.
 */
class DefaultContentSubscriber implements EventSubscriberInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [PreExportEvent::class => 'preExport'];
  }

  /**
   * Reacts before exporting content.
   *
   * @param \Drupal\Core\DefaultContent\PreExportEvent $event
   *   The event being handled.
   */
  public function preExport(PreExportEvent $event): void {
    $event->setCallback('field_item:typed_relation', $this->exportReference(...));
  }

  /**
   * Exports a typed relation field item.
   *
   * @param \Drupal\controlled_access_terms\Plugin\Field\FieldType\TypedRelation $item
   *   The field item to export.
   * @param \Drupal\Core\DefaultContent\ExportMetadata $metadata
   *   Any metadata about the entity being exported (e.g., dependencies).
   *
   * @return array|null
   *   The exported field values, or NULL if no entity is referenced and the
   *   item should not be exported.
   */
  public function exportReference(TypedRelation $item, ExportMetadata $metadata): ?array {
    $entity = $item->get('entity')->getValue();

    // No entity is referenced, so there's nothing else we can do here.
    if ($entity === NULL) {
      $referencer = $item->getEntity();
      $definition = $item->getFieldDefinition();
      $this->logger?->warning('Failed to export reference to @target_type %missing_id referenced by %field on @entity_type %label because the referenced @target_type does not exist.', [
        '@target_type' => (string) $this->entityTypeManager->getDefinition($definition->getFieldStorageDefinition()->getSetting('target_type'))->getSingularLabel(),
        '%missing_id' => $item->get('target_id')->getValue(),
        '%field' => $definition->getLabel(),
        '@entity_type' => (string) $referencer->getEntityType()->getSingularLabel(),
        '%label' => $referencer->label(),
      ]);
      return NULL;
    }

    $values = ['rel_type' => $item->get('rel_type')->getValue()];

    // Referenced paragraphs (and other content entities) don't have portable
    // IDs, so refer to them by UUID instead.
    if ($entity instanceof ContentEntityInterface) {
      $metadata->addDependency($entity);
      $values['entity'] = $entity->uuid();
    }
    else {
      $values['target_id'] = $item->get('target_id')->getValue();
    }

    return $values;
  }

}
