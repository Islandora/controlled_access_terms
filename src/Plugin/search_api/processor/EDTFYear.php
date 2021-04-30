<?php

namespace Drupal\controlled_access_terms\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\controlled_access_terms\EDTFUtils;

/**
 * Adds the item's creation year to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "edtf_year_only",
 *   label = @Translation("EDTF Year"),
 *   description = @Translation("Adds the item's EDTF date as a year."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class EDTFYear extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('EDTF Creation Date Year'),
        'description' => $this->t('The year the item was created'),
        'type' => 'integer',
        'is_list' => TRUE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['edtf_year'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {

    $node = $item->getOriginalObject()->getValue();

    if ($node
        && $node->hasField('field_edtf_date')
        && !$node->field_edtf_date->isEmpty()) {
      $edtf = $node->field_edtf_date->value;
      if ($edtf != "nan" && empty(EDTFUtils::validate($edtf))) {
        $years = [];
        // Sets.
        if (strpos($edtf, '[') !== FALSE || strpos($edtf, '{') !== FALSE) {
          $years = preg_split('/(,|\.\.)/', trim($edtf, '{}[]'));
        }
        // Intervals.
        elseif (strpos($edtf, '/') !== FALSE) {
          $date_range = explode('/', $edtf);
          if ($date_range[0] == '..') {
            // The list of years needs to begin *somewhere*.
            // This is hopefully a sensible default.
            $begin = 0;
          }
          else {
            $begin = $this->edtfToYearInt($date_range[0]);
          }
          if ($date_range[1] == '..') {
            // Similarly, we need to end somewhere. Why not this year?
            $end = intval(date("Y"));
          }
          else {
            $end = $this->edtfToYearInt($date_range[1]);
          }
          $years = range($begin, $end);
        }
        else {
          $years[] = $this->edtfToYearInt($edtf);
        }
        foreach ($years as $year) {
          if (is_numeric($year)) {
            $fields = $item->getFields(FALSE);
            $fields = $this->getFieldsHelper()
              ->filterForPropertyPath($fields, NULL, 'edtf_year');
            foreach ($fields as $field) {
              $field->addValue($year);
            }
          }
        }
      }
    }
  }

  /**
   * Convert a given EDTF date string into a year integer.
   */
  private function edtfToYearInt(string $edtf) {
    $iso = EDTFUtils::iso8601Value($edtf);
    $components = explode('-', $iso);
    return intval(array_shift($components));
  }

}
