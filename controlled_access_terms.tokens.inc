<?php

/**
 * @file
 * Contains controlled_access_terms.tokens.inc.
 *
 * This file provides islandora tokens.
 */

use Drupal\taxonomy\Entity\Term;
use Drupal\controlled_access_terms\EDTFUtils;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function controlled_access_terms_token_info() {
  $type = [
    'name' => t('Islandora Tokens'),
    'description' => t('Tokens for Islandora objects.'),
  ];
  $node['title'] = [
    'name' => t("Node Title"),
    'description' => t("The node's title"),
  ];
  $node['agent_author'] = [
    'name' => t("Author"),
    'description' => t("The node's Author linked agents"),
  ];
  $node['agent_contributor'] = [
    'name' => t("Contributors"),
    'description' => t("The node's Contributor linked agents"),
  ];
  $node['agent_publisher'] = [
    'name' => t("Publishers"),
    'description' => t("The node's Publishers linked agents"),
  ];
  $node['agent_creators'] = [
    'name' => t("Creators"),
    'description' => t("The node's Creators linked agents"),
  ];
  $node['publication_date'] = [
    'name' => t("Publication date"),
    'description' => t('Show the "Date Created" into YYYY/MM/DD format (handles EDTF format)'),
  ];

  return [
    'types' => ['islandoratokens' => $type],
    'tokens' => ['islandoratokens' => $node],
  ];
}

/**
 * Implements hook_tokens().
 */
function controlled_access_terms_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'islandoratokens' && !empty($data['node'])) {
    if (!is_array($tokens) || empty($tokens)) {
      \Drupal::logger('controlled_access_terms')
        ->alert('Tokens not correct format: @tokens', [
          '@tokens' => print_r($tokens, 1),
        ]);
      return;
    }
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'title':
          $replacements[$original] = $data['node']->getTitle();
          break;

        case 'agent_author':
          // For the Linked agent field, the Author/s are specified
          // by the relationship "aut" for Author.
          $replacements[$original] = controlled_access_terms_get_term_with_rel_type($data['node'], 'field_linked_agent', 'relators:aut', TRUE);
          break;

        case 'agent_contributor':
          // For the Linked agent field, the Contributor/s are specified
          // by the relationship "ctb".
          $replacements[$original] = controlled_access_terms_get_term_with_rel_type($data['node'], 'field_linked_agent', 'relators:ctb', TRUE);
          break;

        case 'agent_creators':
          // For the Linked agent field, Creators are any relationship value.
          $replacements[$original] = controlled_access_terms_get_term_with_rel_type($data['node'], 'field_linked_agent', '');
          break;

        case 'agent_publisher':
          // For the Linked agent field, Publishers are any relationship value.
          $replacements[$original] = controlled_access_terms_get_term_with_rel_type($data['node'], 'field_linked_agent', 'relators:pbl');
          break;

        case 'publication_date':
          $replacements[$original] = controlled_access_terms_normalize_date_format($data['node'], 'field_edtf_date_created', $original);
          break;
      }
    }
  }
  return $replacements;
}

/**
 * Helper function to load values for a taxonomy term with a relationship type.
 *
 * @param object $node
 *   A core drupal node object.
 * @param string $field_name
 *   The name of the node's field to check for the specific relationship.
 * @param string $relation_type
 *   Optional value to check the rel_type of the taxonomy term against. When
 *   not provided, any terms returned for the field will match.
 * @param bool $remove_comma
 *   Will flip the string parts of a CSV.
 *
 * @return string
 *   The tokenized value for the given data.
 */
function controlled_access_terms_get_term_with_rel_type($node, $field_name, $relation_type = '', $remove_comma = FALSE) {
  $settings = \Drupal::config('metatag.settings');
  $separator = $settings->get('separator');
  $separator = ($separator) ? $separator : ',';
  $matches = [];
  $field = ($node->hasField($field_name) ? $node->get($field_name) : NULL);
  if (is_object($field)) {
    $tids = $field->getValue();
    foreach ($tids as $tid) {
      // Inspect the taxonomy term.
      if (
        is_array($tid) &&
        array_key_exists('target_id', $tid)
      ) {
        if ($relation_type && array_key_exists('rel_type', $tid)) {
          if ($tid['rel_type'] == $relation_type) {
            $term = Term::load($tid['target_id']);
            if ($term) {
              $matches[] = ($separator == ',') ? controlled_access_terms_remove_comma($term->getName(), $remove_comma) : $term->getName();
            }
          }
        }
        else {
          $term = Term::load($tid['target_id']);
          if ($term) {
            $matches[] = ($separator == ',') ? controlled_access_terms_remove_comma($term->getName(), $remove_comma) : $term->getName();
          }
        }
      }
    }
  }
  return implode($separator, $matches);
}

/**
 * Helper function to normalize a date value that could be an EDTF date value.
 *
 * @param object $node
 *   A core drupal node object.
 * @param string $field_name
 *   The name of the node's field to check for the specific relationship.
 * @param string $original
 *   This is the token value that is being processed - for logging purposes.
 *
 * @return string
 *   The tokenized value for the given data.
 *
 * @throws Exception
 */
function controlled_access_terms_normalize_date_format($node, $field_name, $original) {
  $retval = '';
  try {
    $created_date = ($node->hasField($field_name) ? $node->get($field_name) : NULL);
    if (!is_null($created_date)) {
      if (
        is_array($created_date->getValue($field_name)) &&
        array_key_exists(0, $created_date->getValue($field_name)) &&
        is_array($created_date->getValue($field_name)[0]) &&
        array_key_exists('value', $created_date->getValue($field_name)[0])
      ) {
        $v = $created_date->getValue($field_name)[0]['value'];
        if (strlen($v) < 6) {
          throw new Exception('Date does not have enough digits.');
        }
        $date_val = new DateTime($v);
        $retval = $date_val->format('Y/m/d');
      }
    }
  }
  catch (Exception $e) {
    if (strpos($field_name, 'edtf') != FALSE) {
      $date = $node->$field_name->value;
      $iso = EDTFUtils::iso8601Value($date);
      $components = explode('-', $iso);
      $year = array_shift($components);
      if (strpos($year, 'T') != FALSE) {
        $year_parts = explode('T', $year);
        $year = $year_parts[0];
      }
      if (is_numeric($year)) {
        return $year;
      }
    }
    // Date value could not be converted to a string.
    \Drupal::logger('controlled_access_terms')->notice(
      'During token generation, a date ' .
        'value: @date could not be converted to a DateTime object, used token "@token_value".',
      [
        '@date' => @$created_date->getValue($field_name)[0]['value'],
        '@token_value' => $original,
      ]
    );
  }
  return $retval;
}

/**
 * Conditionally removes any commas and reverse the order of the string values.
 *
 * @param string $input
 *   The string value to potentially remove and flip parts.
 * @param bool $remove_comma
 *   Whether or not to remove the comma and flip the parts.
 *
 * @return string
 *   The value of the label.
 */
function controlled_access_terms_remove_comma($input, $remove_comma = FALSE) {
  if ($remove_comma) {
    $parts = explode(",", $input);
    $parts = array_reverse($parts);
    return implode(" ", $parts);
  }
  else {
    return $input;
  }
}
