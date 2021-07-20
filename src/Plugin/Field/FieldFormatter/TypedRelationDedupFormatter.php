<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'TypedRelationFormatter'.
 *
 * @FieldFormatter(
 *   id = "typed_relation_dedup",
 *   label = @Translation("Typed Relation Dedup Formatter"),
 *   field_types = {
 *     "typed_relation"
 *   }
 * )
 */
class TypedRelationDedupFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $unique_tids = [];
    foreach ($items as $delta => $item) {
      $this_tid = $item->target_id;
      $delta_to_update = in_array($this_tid, $unique_tids);
      $rel_types = $item->getRelTypes();
      $rel_type = isset($rel_types[$item->rel_type]) ? $rel_types[$item->rel_type] : $item->rel_type;
      if (!$delta_to_update) {
        $unique_tids[$delta] = $this_tid;
        if (!empty($rel_type)) {
          $elements[$delta]['#prefix'] = $rel_type . ': ';
        }
      }
      else {
        if (!empty($rel_type)) {
          $delta_to_update = array_search($this_tid, $unique_tids);
          $prefix_before = $elements[$delta_to_update]['#prefix'];
          $prefix_parts = explode(": ", $prefix_before);
          if (!empty($prefix_parts[0])) {
            $prefix_parts[0] = $prefix_parts[0] . ", ";
          }
          $elements[$delta_to_update]['#prefix'] = $prefix_parts[0] . $rel_type . ': ';
        }
        unset($elements[$delta]);
      }

    }

    return $elements;
  }

}
