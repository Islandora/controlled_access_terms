<?php

namespace Drupal\controlled_access_terms\Plugin\search_api\processor;

use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a Search API processor for indexing EDTF dates in Solr.
 *
 * @SearchApiProcessor(
 *   id = "edtf_date_processor",
 *   label = @Translation("EDTF Date Processor"),
 *   description = @Translation("Indexes EDTF dates (single or multiple) as Solr Date Types."),
 *   stages = {
 *     "add_properties" = 0,
 *   }
 * )
 */
class EDTFDateProcessor extends ProcessorPluginBase implements PluginFormInterface {
  use PluginFormTrait;
  use StringTranslationTrait;

  /**
   * Stores plugin configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
      'open_start_year' => 0,
      'open_end_year' => '',
      'spring_date' => 3,
      'summer_date' => 6,
      'autumn_date' => 9,
      'winter_date' => 12,

    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#description'] = $this->t('Select the EDTF fields to extract dates from.
            <br>Note: The following EDTF date formats are supported:
                <ul>
                <li>Year only: YYYY (e.g. "2012")</li>
                <li>Year and month only: YYYY-MM (e.g. "2012-05")</li>
                <li>Full date: YYYY-MM-DD (e.g. "2012-05-01")</li>
                <li>Multiple dates:{YYYY, YYYY-MM, YYYY-MM-DD, ...}</li>
                <li>Date ranges: YYYY/YYYY</li>
                <li> Dates with unknown parts: YYYY-X, YYYY-MM-X, YYYY-MM-DD-X</li>
                <li> Dates with sub-year groupings: YYYY-21 to YYYY-41</li>
                <ul>');

    $fields = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties(['field_type' => 'edtf']);

    $fields_options = [];
    foreach ($fields as $field) {
      $key = $field->getTargetEntityTypeId() . '|' . $field->getName();
      $fields_options[$key] = $this->t(
            '@label (Entity: @entity_type)', [
              '@label' => $field->label(),
              '@entity_type' => $field->getTargetEntityTypeId(),
            ]
        );
    }

    $form['fields'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('EDTF Fields'),
      '#description' => $this->t('Select one or more EDTF fields to index.'),
      '#options' => $fields_options,
      '#default_value' => $this->configuration['fields'],
    ];

    $form['open_start_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Open Interval Begin Year'),
      '#description' => $this->t('Sets the beginning year to begin indexing from. Leave blank if you would like to index from year 1000.'),
      '#default_value' => $this->configuration['open_start_year'],
    ];
    $form['open_end_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Open Interval End Year'),
      '#description' => $this->t('Sets the end year to end indexing at. Leave blank if you would like to index up to date 9999.'),
      '#default_value' => $this->configuration['open_end_year'],
    ];
    $form['spring_date'] = [
      '#type' => 'number',
      '#title' => $this->t('Month to Map Spring to'),
      '#description' => $this->t('What month Spring should map to. This is used for seasons 21, 25, and 29.'),
      '#default_value' => $this->configuration['spring_date'],
    ];
    $form['summer_date'] = [
      '#type' => 'number',
      '#title' => $this->t('Month to Map Summer to'),
      '#description' => $this->t('What month Summer should map to. This is used for seasons 22, 26, and 30.'),
      '#default_value' => $this->configuration['summer_date'],
    ];
    $form['autumn_date'] = [
      '#type' => 'number',
      '#title' => $this->t('Month to Map Autumn to'),
      '#description' => $this->t('What month Autumn should map to. This is used for seasons 23, 27, and 31.'),
      '#default_value' => $this->configuration['autumn_date'],
    ];
    $form['winter_date'] = [
      '#type' => 'number',
      '#title' => $this->t('Month to Map Winter to'),
      '#description' => $this->t('What month Winter should map to. This is used for seasons 24, 28, and 32.'),
      '#default_value' => $this->configuration['winter_date'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('open_start_year') < 0) {
      $form_state->setErrorByName('open_start_year', $this->t('Open start year must be a positive integer.'));
    }

    if (!empty($form_state->getValue('open_end_year')) && $form_state->getValue('open_end_year') < $form_state->getValue('open_start_year')) {
      $form_state->setErrorByName('open_end_year', $this->t('Open end year must be greater than or equal to open start year.'));
    }

    // Make sure given months for seasons are valid.
    if ($form_state->getValue('spring_date') < 1 || $form_state->getValue('spring_date') > 12) {
      $form_state->setErrorByName('spring_date', $this->t('Spring month must be between 1 and 12.'));
    }
    if ($form_state->getValue('summer_date') < 1 || $form_state->getValue('summer_date') > 12) {
      $form_state->setErrorByName('summer_date', $this->t('Summer month must be between 1 and 12.'));
    }
    if ($form_state->getValue('autumn_date') < 1 || $form_state->getValue('autumn_date') > 12) {
      $form_state->setErrorByName('autumn_date', $this->t('Autumn month must be between 1 and 12.'));
    }
    if ($form_state->getValue('winter_date') < 1 || $form_state->getValue('winter_date') > 12) {
      $form_state->setErrorByName('winter_date', $this->t('Winter month must be between 1 and 12.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['fields'] = $form_state->getValue('fields', []);
    $this->configuration['ignore_open_start'] = $form_state->getValue('ignore_open_start');
    $this->configuration['ignore_open_end'] = $form_state->getValue('ignore_open_end');
    $this->configuration['open_start_year'] = $form_state->getValue('open_start_year');
    $this->configuration['open_end_year'] = $form_state->getValue('open_end_year');
    $this->configuration['spring_date'] = $form_state->getValue('spring_date');
    $this->configuration['summer_date'] = $form_state->getValue('summer_date');
    $this->configuration['autumn_date'] = $form_state->getValue('autumn_date');
    $this->configuration['winter_date'] = $form_state->getValue('winter_date');
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $data_definition = \Drupal::typedDataManager()->createDataDefinition('datetime_iso8601')
        ->setLabel($this->t('EDTF Dates'))
        ->setDescription($this->t('Indexes single EDTF dates or multiple separate dates.'));

      $properties['edtf_dates'] = new ProcessorProperty(
            [
              'label' => $this->t('EDTF Dates'),
              'description' => $this->t('Indexes single EDTF dates or multiple separate dates.'),
              'type' => 'datetime_iso8601',
              'is_list' => TRUE,
              'processor_id' => $this->getPluginId(),
              'data_definition' => $data_definition,
            ]
        );
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();
    $edtfDates = [];

    foreach ($this->configuration['fields'] as $field_key) {
      if (strpos($field_key, '|') === FALSE) {
        continue;
      }
      [$entity_type, $field_name] = explode('|', $field_key, 2);
      if ($entity->getEntityTypeId() !== $entity_type) {
        continue;
      }
      if (!$entity->hasField($field_name)) {
        continue;
      }

      $field_values = $entity->get($field_name)->getValue();
      foreach ($field_values as $date_item) {
        if (!empty($date_item['value'])) {
          // Sanitize the input value before processing.
          $value = $this->sanitizeEdtfString($date_item['value']);

          if ($this->isSingleEdtfDate($value)) {
            $edtfDates[] = $this->convertEdtftoSolr($value);
          }
          elseif ($this->isEdtfMultiDate($value)) {
            $dates = $this->convertEdtfMultiDateToSolr($value);
            if ($dates) {
              $edtfDates = array_merge($edtfDates, $dates);
            }
          }
        }
      }
    }

    $filteredDates = [];
    foreach ($edtfDates as $date) {
      $year = (int) substr($date, 0, 4);
      if (!$this->configuration['ignore_open_start'] && $this->configuration['open_start_year'] > 0 && $year < $this->configuration['open_start_year']) {
        continue;
      }
      if (!$this->configuration['ignore_open_end'] && !empty($this->configuration['open_end_year']) && $year > $this->configuration['open_end_year']) {
        continue;
      }
      $filteredDates[] = $date;
    }
    $edtfDates = $filteredDates;

    // Sort dates in ascending order.
    usort(
          $edtfDates, function ($a, $b) {
              return strtotime($a) - strtotime($b);
          }
      );

    if (!empty($edtfDates)) {
      $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'edtf_dates');
      foreach ($fields as $field) {
        $field->setValues($edtfDates);
      }
    }
  }

  /**
   * Checks if the provided value is a single (possibly incomplete) EDTF date.
   *
   * Accepted formats:
   * - YYYY (e.g. "2012")
   * - YYYY-MM (e.g. "2012-05")
   * - YYYY-MM-DD (e.g. "2012-05-01")
   *
   * Allows X placeholders.
   */
  protected function isSingleEdtfDate($value) {
    if (!is_string($value)) {
      return FALSE;
    }
    if (preg_match('/^\.\.\/(.+)/', $value, $matches)) {
      $start_year = !empty($this->configuration['open_start_year'])
                ? $this->configuration['open_start_year']
                : '1000';
      $value = $start_year . '/' . $matches[1];
    }
    if (preg_match('/(.+)\/\.\.$/', $value, $matches)) {
      $end_year = !empty($this->configuration['open_end_year'])
                ? $this->configuration['open_end_year']
                : '9999';
      $value = $matches[1] . '/' . $end_year;
    }
    /* Check for EDTF date with a range (e.g. "YYYY/YYYY") */
    if (strpos($value, '/') !== FALSE) {
      $parts = explode('/', $value);
      $value = trim($parts[0]);
    }
    /* Check for extended year notation: value starting with 'Y' */
    if (strpos($value, 'Y') === 0) {
      $candidate = substr($value, 1);
      return preg_match('/^-?[0-9]{5,}$/', $candidate);
    }
    return preg_match('/^[0-9X]{4}(-[0-9X]{2}(-[0-9X]{2})?)?$/', $value);
  }

  /**
   * Checks if is multiple EDTF date value (wrapped in curly braces).
   *
   * Accepted format:
   * - {YYYY, YYYY-MM, YYYY-MM-DD, ...}
   */
  protected function isEdtfMultiDate($value) {
    return is_string($value) && preg_match('/^{\s*([0-9X]{4}(-[0-9X]{2}(-[0-9X]{2})?)?)(\s*,\s*[0-9X]{4}(-[0-9X]{2}(-[0-9X]{2})?)?)*\s*}$/', $value);
  }

  /**
   * Converts a single EDTF date to a Solr-compatible format.
   */
  protected function convertEdtftoSolr($value) {
    if (preg_match('/^\.\.\/(.+)/', $value, $matches)) {
      $start_year = !empty($this->configuration['open_start_year'])
                ? $this->configuration['open_start_year']
                : '1000';
      $value = $start_year . '/' . $matches[1];
    }

    if (preg_match('/(.+)\/\.\.$/', $value, $matches)) {
      $end_year = !empty($this->configuration['open_end_year'])
                ? $this->configuration['open_end_year']
                : '9999';
      $value = $matches[1] . '/' . $end_year;
    }

    if (strpos($value, '/') !== FALSE) {
      $parts = explode('/', $value);
      $value = trim($parts[0]);
    }

    if (strpos($value, 'Y') === 0) {
      $value = substr($value, 1);
    }

    // Sanitize and normalize the EDTF string.
    $value = $this->sanitizeEdtfString($value);
    $value = $this->normalizePlaceholders($value);

    // Validate numeric constraints.
    $parts = explode('-', $value);
    if (count($parts) >= 2) {
      $month = (int) $parts[1];
      if (!(($month >= 1 && $month <= 12) || ($month >= 21 && $month <= 41))) {
        \Drupal::logger('edtf_date_processor')->warning('Month out of acceptable range in date: "@value". Month should be between 01 and 12, or 21 and 41.', ['@value' => $value]);
      }
    }
    if (count($parts) == 3) {
      $day = (int) $parts[2];
      if ($day < 1 || $day > 31) {
        \Drupal::logger('edtf_date_processor')->warning('Day out of acceptable range in date: "@value". Day should be between 01 and 31.', ['@value' => $value]);
      }
    }

    // Ensure complete date format.
    switch (TRUE) {
      case preg_match('/^\d{4}-\d{2}-\d{2}$/', $value):
        break;

      case preg_match('/^\d{4}-\d{2}$/', $value):
        // Convert sub-year groupings to months.
        $parts = explode('-', $value);
        $month = $this->mapMonth($parts[1]);
        $value = $parts[0] . '-' . $month . '-01';
        break;

      case preg_match('/^\d{4}$/', $value):
        $value .= '-01-01';
        break;

      // Handle extended year notation (e.g. "YYYYY").
      case preg_match('/^-?[0-9]{5,}$/', $value):
        $value .= '-01-01';
        \Drupal::logger('edtf_date_processor')->warning('This is an extended year date, this might parse unexpectedly and cause issues.');
        break;
    }
    // Check if the date is the expected format after appending missing parts.
    if (!preg_match('/^-?\d{4}-\d{2}-\d{2}$/', $value)) {
      \Drupal::logger('edtf_date_processor')->warning('Date value after appending missing parts does not match the expected pattern: "@value".', ['@value' => $value]);
    }

    return $value . 'T00:00:00Z';
  }

  /**
   * Converts a multiple EDTF date value to an array of Solr-compatible dates.
   */
  protected function convertEdtfMultiDateToSolr($value) {
    if (preg_match_all('/[0-9X]{4}(?:-[0-9X]{2}(?:-[0-9X]{2})?)?/', $value, $matches)) {
      $converted = [];
      foreach ($matches[0] as $raw_date) {
        $converted[] = $this->convertEdtftoSolr($raw_date);
      }
      return $converted;
    }
    return [];
  }

  /**
   * Removes unwanted special characters from the EDTF string.
   */
  protected function sanitizeEdtfString($value) {
    return str_replace(["~", "?", "%"], "", $value);
  }

  /**
   * Replaces X placeholders in the EDTF string.
   *
   * For the year, replaces all X with 0.
   * For month/day, replaces "XX" with "01".
   */
  protected function normalizePlaceholders($value) {
    $parts = explode('-', $value);
    $parts[0] = str_replace('X', '0', $parts[0]);
    if (isset($parts[1])) {
      if ($parts[1] === 'XX') {
        $parts[1] = '01';
      }
      else {
        $parts[1] = str_replace('X', '0', $parts[1]);
        if ($parts[1] === '00') {
          $parts[1] = '01';
        }
      }
    }
    if (isset($parts[2])) {
      if ($parts[2] === 'XX') {
        $parts[2] = '01';
      }
      else {
        $parts[2] = str_replace('X', '0', $parts[2]);
        if ($parts[2] === '00') {
          $parts[2] = '01';
        }
      }
    }
    return implode('-', $parts);
  }

  /**
   * Maps the given sub-year grouping to a month.
   *
   * Returns a 2 digit string between 01 and 12.
   */
  protected function mapMonth($month) {
    $map = [
      '01' => 1,
      '02' => 2,
      '03' => 3,
      '04' => 4,
      '05' => 5,
      '06' => 6,
      '07' => 7,
      '08' => 8,
      '09' => 9,
      '10' => 10,
      '11' => 11,
      '12' => 12,
      '21' => $this->configuration['spring_date'],
      '22' => $this->configuration['summer_date'],
      '23' => $this->configuration['autumn_date'],
      '24' => $this->configuration['winter_date'],
      '25' => $this->configuration['spring_date'],
      '26' => $this->configuration['summer_date'],
      '27' => $this->configuration['autumn_date'],
      '28' => $this->configuration['winter_date'],
      '29' => $this->configuration['spring_date'],
      '30' => $this->configuration['summer_date'],
      '31' => $this->configuration['autumn_date'],
      '32' => $this->configuration['winter_date'],
      '33' => 1,
      '34' => 4,
      '35' => 7,
      '36' => 10,
      '37' => 1,
      '38' => 5,
      '39' => 8,
      '40' => 1,
      '41' => 7,
    ];

    return str_pad($map[$month], 2, '0', STR_PAD_LEFT);
  }

}
