<?php

namespace Drupal\controlled_access_terms\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
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
 * )
 */
class EDTFYear extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

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
  public function defaultConfiguration() {
    return [
      'fields' => [],
      'ignore_undated' => TRUE,
      'ignore_open_dates' => FALSE,
      'open_start_year' => 0,
      'open_end_year' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form['#description'] = $this->t('Select the fields containing EDTF strings to extract year values for.');
    $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties(['field_type' => 'edtf']);
    $fields_options = [];
    foreach ($fields as $field) {
      $fields_options[$field->getTargetBundle() . '|' . $field->get('field_name')] = $this->t("%label (%bundle)", [
        '%label' => $field->label(),
        '%bundle' => $field->getTargetBundle(),
      ]);
    }
    $form['fields'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Fields'),
      '#description' => $this->t('Select the fields with EDTF values to use.'),
      '#options' => $fields_options,
      '#default_value' => $this->configuration['fields'],
    ];

    $form['ignore_undated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore Undated'),
      '#description' => $this->t('Ignore undated values (i.e. "EDTF").'),
      '#default_value' => $this->configuration['ignore_undated'],
    ];
    $form['ignore_open_dates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore Open Dates'),
      '#description' => $this->t('Ignores the open dates (".."). E.g. both "../2021" and "2021/.." would be indexed as "2021" instead of date ranges.'),
      '#default_value' => $this->configuration['ignore_open_dates'],
    ];
    $form['open_start_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Open Interval Begin Year'),
      '#description' => $this->t('Sets the beginning year to be used when processing an date interval. For example, by default, "../%year" would become every year from 0 until %year', ['%year' => date("Y")]),
      '#default_value' => $this->configuration['open_start_year'],
    ];
    $form['open_end_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Open Interval End Year'),
      '#description' => $this->t('Sets the last year to be used when processing an date interval. Leave blank to use the current year when last indexed. For example, by default, "2020/.." would become every year from 2020 until this year (%year).', ['%year' => date("Y")]),
      '#default_value' => $this->configuration['open_end_year'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $formState) {
    if (!is_numeric($formState->getValue('open_start_year'))) {
      $formState->setError($form['open_start_year'], $this->t('Please provide an integer year value.'));
    }
    if (!empty($formState->getValue('open_end_year')) && !is_numeric($formState->getValue('open_end_year'))) {
      $formState->setError($form['open_end_year'], $this->t('Please leave the field blank or provide an integer year value.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {

    $entity = $item->getOriginalObject()->getValue();

    foreach ($this->configuration['fields'] as $field) {
      list($bundle, $field_name) = explode('|', $field, 2);
      if ($entity
          && $entity->bundle() == $bundle
        && $entity->hasField($field_name)
        && !$entity->get($field_name)->isEmpty()) {
        $edtf = $entity->get($field_name)->value;
        if ($edtf != "nan" && empty(EDTFUtils::validate($edtf))) {
          if ($this->configuration['ignore_undated'] && $edtf == "XXXX") {
            continue;
          }
          $years = [];
          // Sets.
          if (strpos($edtf, '[') !== FALSE || strpos($edtf, '{') !== FALSE) {
            $years = preg_split('/(,|\.\.)/', trim($edtf, '{}[]'));
          }
          // Intervals.
          elseif ($this->configuration['ignore_open_dates'] && strpos($edtf, '..') !== FALSE) {
            $years[] = $this->edtfToYearInt(trim($edtf,'./'));
          }
          elseif (strpos($edtf, '/') !== FALSE) {
            $date_range = explode('/', $edtf);
            if ($date_range[0] == '..') {
              // The list of years needs to begin *somewhere*.
              $begin = $this->configuration['open_start_year'];
            }
            else {
              $begin = $this->edtfToYearInt($date_range[0]);
            }
            if ($date_range[1] == '..') {
              // Similarly, we need to end somewhere.
              // Use this year if none was configured.
              $end = (empty($this->configuration['open_end_year'])) ? intval(date("Y")) : $this->configuration['open_end_year'];
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
