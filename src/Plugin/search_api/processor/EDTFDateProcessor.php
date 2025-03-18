<?php

namespace Drupal\controlled_access_terms\Plugin\search_api\processor;

use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\controlled_access_terms\EDTFUtils;

/**
 * Provides a Search API processor for indexing EDTF dates (single and multiple dates) in Solr.
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
class EDTFDateProcessor extends ProcessorPluginBase implements PluginFormInterface
{
    use PluginFormTrait;
    use StringTranslationTrait;
  
    /**
     * @var array Stores plugin configuration 
     */
    protected $configuration;
  
    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return [
            'fields' => [],
            'ignore_open_start' => false,
            'ignore_open_end' => false,
            'open_start_year' => 0,
            'open_end_year' => '',
        ];
    }
  
    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form['#description'] = $this->t('Select the EDTF fields to extract dates from.');
  
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
            '#multiple' => true,
            '#title' => $this->t('EDTF Fields'),
            '#description' => $this->t('Select one or more EDTF fields to index.'),
            '#options' => $fields_options,
            '#default_value' => $this->configuration['fields'],
        ];
  
        $form['open_start_year'] = [
            '#type' => 'number',
            '#title' => $this->t('Open Interval Begin Year'),
            '#description' => $this->t('Sets the beginning year to begin indexing from. Leave blank if you would like to index from the smallest year found in the data.'),
            '#default_value' => $this->configuration['open_start_year'],
        ];
        $form['open_end_year'] = [
            '#type' => 'number',
            '#title' => $this->t('Open Interval End Year'),
            '#description' => $this->t('Sets the end year to end indexing at. Leave blank if you would like to index to the largest year found in the data.'),
            '#default_value' => $this->configuration['open_end_year'],
        ];
  
        return $form;
    }
  
    /**
     * {@inheritdoc}
     */
    public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        if ($form_state->getValue('open_start_year') < 0) {
            $form_state->setErrorByName('open_start_year', $this->t('Open start year must be a positive integer.'));
        }
    
        if (!empty($form_state->getValue('open_end_year')) && $form_state->getValue('open_end_year') < $form_state->getValue('open_start_year')) {
            $form_state->setErrorByName('open_end_year', $this->t('Open end year must be greater than or equal to open start year.'));
        }
    }
  
    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        $this->configuration['fields'] = $form_state->getValue('fields', []);
        $this->configuration['ignore_open_start'] = $form_state->getValue('ignore_open_start');
        $this->configuration['ignore_open_end'] = $form_state->getValue('ignore_open_end');
        $this->configuration['open_start_year'] = $form_state->getValue('open_start_year');
        $this->configuration['open_end_year'] = $form_state->getValue('open_end_year');
    }
  
    /**
     * {@inheritdoc}
     */
    public function getPropertyDefinitions(?DatasourceInterface $datasource = null)
    {
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
                'is_list' => true,
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
    public function addFieldValues(ItemInterface $item)
    {
        $entity = $item->getOriginalObject()->getValue();
        $edtfDates = [];
  
        foreach ($this->configuration['fields'] as $field_key) {
            if (strpos($field_key, '|') === false) {
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
                    $value = $this->sanitizeEDTFString($date_item['value']);
  
                    if ($this->isSingleEDTFDate($value)) {
                        $edtfDates[] = $this->convertEDTFtoSolr($value);
                    }
                    elseif ($this->isEDTFMultiDate($value)) {
                        $dates = $this->convertEDTFMultiDateToSolr($value);
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
            $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), null, 'edtf_dates');
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
    protected function isSingleEDTFDate($value)
    {
        return is_string($value) && preg_match('/^[0-9X]{4}(-[0-9X]{2}(-[0-9X]{2})?)?$/', $value);
    }
  
    /**
     * Checks if the provided value is a multiple EDTF date value (wrapped in curly braces).
     *
     * Accepted format:
     * - {YYYY, YYYY-MM, YYYY-MM-DD, ...}
     */
    protected function isEDTFMultiDate($value)
    {
        return is_string($value) && preg_match('/^{\s*([0-9X]{4}(-[0-9X]{2}(-[0-9X]{2})?)?)(\s*,\s*[0-9X]{4}(-[0-9X]{2}(-[0-9X]{2})?)?)*\s*}$/', $value);
    }
  
    /**
     * Converts a single (possibly incomplete) EDTF date to a Solr-compatible format.
     */
    protected function convertEDTFtoSolr($value)
    {
        // Sanitize and normalize the EDTF string.
        $value = $this->sanitizeEDTFString($value);
        $value = $this->normalizeXPlaceholders($value);
  
        // Handle season mapping for YYYY-MM format if applicable.
        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            if (isset(EDTFUtils::SEASONS_MAP[$month])) {
                $mapped_month = EDTFUtils::SEASONS_MAP[$month];
                return "{$year}-{$mapped_month}-01T00:00:00Z";
            }
        }
  
        // Ensure complete date format.
        switch (true) {
        case preg_match('/^\d{4}-\d{2}-\d{2}$/', $value):
            break;
        case preg_match('/^\d{4}-\d{2}$/', $value):
            $value .= '-01';
            break;
        case preg_match('/^\d{4}$/', $value):
            $value .= '-01-01';
            break;
        }
        return $value . 'T00:00:00Z';
    }
  
    /**
     * Converts a multiple EDTF date value to an array of Solr-compatible dates.
     */
    protected function convertEDTFMultiDateToSolr($value)
    {
        if (preg_match_all('/[0-9X]{4}(?:-[0-9X]{2}(?:-[0-9X]{2})?)?/', $value, $matches)) {
            $converted = [];
            foreach ($matches[0] as $raw_date) {
                $converted[] = $this->convertEDTFtoSolr($raw_date);
            }
            return $converted;
        }
        return [];
    }
  
    /**
     * Removes unwanted special characters from the EDTF string.
     */
    protected function sanitizeEDTFString($value)
    {
        return str_replace(["~", "?", "%"], "", $value);
    }
  
    /**
     * Replaces X placeholders in the EDTF string.
     *
     * For the year, replaces all X with 0.
     * For month/day, replaces "XX" with "01", else replaces X with 0 and fixes "00" to "01".
     */
    protected function normalizeXPlaceholders($value)
    {
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
}
