<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'AuthorityLinkFormatter'.
 *
 * @FieldFormatter(
 *   id = "authority_formatter_default",
 *   label = @Translation("Authority link formatter"),
 *   field_types = {
 *     "authority_link"
 *   }
 * )
 */
class AuthorityLinkFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    // Open link in a new window by default.
    $settings['target'] = '_blank';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $entity = $items
      ->getEntity();
    $settings = $this
      ->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $url = $this->buildUrl($item);

      // drupal_set_message('Settings: '.print_r($item->getSources(),TRUE));
      $sources = $item->getSources();
      $link_title = !empty($sources[$item->source]) ? $sources[$item->source] : $item->source;

      // If the title field value is available, use it for the link text.
      if (empty($settings['url_only']) && !empty($item->title)) {

        // Unsanitized token replacement here because the entire link title
        // gets auto-escaped during link generation in
        // \Drupal\Core\Utility\LinkGenerator::generate().
        $link_title = \Drupal::token()
          ->replace($item->title, [
            $entity
              ->getEntityTypeId() => $entity,
          ], [
            'clear' => TRUE,
          ]);
      }

      // Trim the link text to the desired length.
      if (!empty($settings['trim_length'])) {
        $link_title = Unicode::truncate($link_title, $settings['trim_length'], FALSE, TRUE);
      }
      if (!empty($settings['url_only']) && !empty($settings['url_plain'])) {
        $element[$delta] = [
          '#plain_text' => $link_title,
        ];
        if (!empty($item->_attributes)) {

          // Piggyback on the metadata attributes, which will be placed in the
          // field template wrapper, and set the URL value in a content
          // attribute.
          // @todo Does RDF need a URL rather than an internal URI here?
          // @see \Drupal\Tests\rdf\Kernel\Field\LinkFieldRdfaTest.
          $content = str_replace('internal:/', '', $item->uri);
          $item->_attributes += [
            'content' => $content,
          ];
        }
      }
      else {
        $element[$delta] = [
          '#type' => 'link',
          '#title' => $link_title,
          '#options' => $url
            ->getOptions(),
        ];
        $element[$delta]['#url'] = $url;
        if (!empty($item->_attributes)) {
          $element[$delta]['#options'] += [
            'attributes' => [],
          ];
          $element[$delta]['#options']['attributes'] += $item->_attributes;

          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
    }
    return $element;
  }

}
