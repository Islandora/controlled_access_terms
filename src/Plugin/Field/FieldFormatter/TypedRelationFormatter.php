<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;

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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {

      $rel_types = $item->getRelTypes();
      $rel_type = isset($rel_types[$item->rel_type]) ? $rel_types[$item->rel_type] : $item->rel_type;

      $elements[$delta]['#prefix'] = $rel_type . ': ';
    }

    return $elements;
  }

}
