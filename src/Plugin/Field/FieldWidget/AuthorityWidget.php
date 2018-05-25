<?php

namespace Drupal\controlled_access_terms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
/**
 * Plugin implementation of the 'authority_link_default' widget.
 *
 *
 * @FieldWidget(
 *   id = "authority_link_default",
 *   label = @Translation("Authority Link Widget"),
 *   field_types = {
 *     "authority_link"
 *   }
 * )
 */

class AuthorityWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // $element = parent::formElement($items,$delta,$element, $form, $form_state);
    // Item of interest
    $item =& $items[$delta];
    $settings = $item->getFieldDefinition()->getSettings();

    //Load up the form fields
    $element += array(
      '#type' => 'fieldset',
    );
    $element['source'] = [
      '#title' => t('Source'),
      '#type' => 'select',
      '#options' => $settings['authority_sources'],
      '#default_value' => isset($item->source) ? $item->source : '',
    ];
    $element['uri'] = array(
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#placeholder' => $this
        ->getSetting('placeholder_url'),
      '#default_value' => !$item
        ->isEmpty() ? static::getUriAsDisplayableString($item->uri) : NULL,
      '#element_validate' => array(
        array(
          get_called_class(),
          'validateUriElement',
        ),
      ),
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    );
    $element['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Alternate link text'),
      '#placeholder' => $this->getSetting('placeholder_title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
      '#maxlength' => 255,
      '#description' => t('Text to use in place of the authority source name.')
    );

    return $element;
  }

}
 ?>
