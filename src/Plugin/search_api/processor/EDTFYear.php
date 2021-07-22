<?php

namespace Drupal\controlled_access_terms\Plugin\search_api\processor;

use EDTF\EdtfFactory;
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
      'ignore_open_start' => FALSE,
      'ignore_open_end' => FALSE,
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
      '#description' => $this->t('Ignore undated values (i.e. "XXXX").'),
      '#default_value' => $this->configuration['ignore_undated'],
    ];
    $form['ignore_open_start'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore Open Dates'),
      '#description' => $this->t('Ignores the open start dates. E.g. "../2021" would be indexed as "2021" instead of every year from 0 (or the configured open start year).'),
      '#default_value' => $this->configuration['ignore_open_start'],
    ];
    $form['open_start_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Open Interval Begin Year'),
      '#description' => $this->t('Sets the beginning year to be used when processing an date interval. For example, by default, "../%year" would become every year from 0 until %year', ['%year' => date("Y")]),
      '#default_value' => $this->configuration['open_start_year'],
    ];
    $form['ignore_open_end'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore Open Ended Dates'),
      '#description' => $this->t('Ignores the open ended dates. E.g. "2020/.." would be indexed as "2020" instead of every year from 2020 until %year.', ['%year' => date("Y")]),
      '#default_value' => $this->configuration['ignore_open_end'],
    ];
    $form['open_end_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Open Interval End Year'),
      '#description' => $this->t('Sets the last year to be used when processing an date interval. Leave blank to use the current year when indexed. For example, by default, "2020/.." would become every year from 2020 until this year (%year).', ['%year' => date("Y")]),
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
        $edtf = trim($entity->get($field_name)->value);
        if ($edtf != "nan" && empty(EDTFUtils::validate($edtf))) {
          if ($this->configuration['ignore_undated'] && $edtf == "XXXX") {
            continue;
          }
          try {
            $parser = EdtfFactory::newParser();
            $years = [];
            // Sets.
            if (strpos($edtf, '[') !== FALSE || strpos($edtf, '{') !== FALSE) {
              $dates = $parser->parse($edtf)->getEdtfValue();
              $years = array_map(function ($date) {
                return $date->getYear();
              }, $dates->getDates());
            }
            else {
              // Open start dates.
              if (substr($edtf, 0, 3) === '../') {
                if ($this->configuration['ignore_open_start']) {
                  $edtf = substr($edtf, 3);
                }
                else {
                  $edtf = str_replace('../', $this->configuration['open_start_year'] . '/', $edtf);
                }
              }
              // Open end dates.
              if (substr($edtf, -3) === '/..') {
                if ($this->configuration['ignore_open_end']) {
                  $edtf = substr($edtf, 0, -3);
                }
                else {
                  $end_year = (empty($this->configuration['open_end_year'])) ? date('Y') : $this->configuration['open_end_year'];
                  $edtf = str_replace('/..', '/' . $this->configuration['open_end_year'], $edtf);
                }
              }

              $parsed = $parser->parse($edtf)->getEdtfValue();
              $years = range(intval(date('Y', $parsed->getMin())), intval(date('Y', $parsed->getMax())));
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
          catch (\Throwable $e) {
            \Drupal::logger('controlled_access_terms')->warning(t("Could not parse EDTF value '@edtf' for indexing @type/@id",
            [
              '@edtf' => $edtf,
              '@type' => $entity->getEntityTypeId(),
              '@id' => $entity->id(),
            ]));
          }
        }
      }
    }
  }

}
