<?php

namespace Drupal\controlled_access_terms\Feeds\Target;

use Drupal\controlled_access_terms\EDTFUtils;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\Feeds\Target\StringTarget;


/**
 * Defines an edtf field mapper.
 *
 * @FeedsTarget(
 *   id = "edtf_feeds_target",
 *   field_types = {"edtf"}
 * )
 */

class ExtendedDateTimeFormat extends StringTarget {
  protected $settings;
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $this->targetDefinition = $configuration['target_definition'];
    $this->settings = $this->targetDefinition->getFieldDefinition()->getSettings();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    parent::prepareValue($delta, $values);
    $errors = EDTFUtils::validate($values['value'], $this->settings['intervals'],$this->settings['sets'], $this->settings['strict_dates']);
    if (!empty($errors)){
      throw new TargetValidationException($this->t("Date given as [%date] not EDTF compliant.", ['%date' => $values['value']]));
    }
  }

}
