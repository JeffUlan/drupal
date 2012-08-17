<?php

/**
 * @file
 * Definition of Views\locale\Plugin\views\field\LinkEdit.
 */

namespace Views\locale\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link to edit a translation.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "locale_link_edit",
 *   module = "locale"
 * )
 */
class LinkEdit extends FieldPluginBase {

  function construct() {
    parent::construct();
    $this->additional_fields['lid'] = 'lid';
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['text'] = array('default' => '', 'translatable' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::options_form($form, $form_state);
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function access() {
    // Ensure user has access to edit translations.
    return user_access('translate interface');
  }

  function render($values) {
    $value = $this->get_value($values, 'lid');
    return $this->render_link($this->sanitize_value($value), $values);
  }

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = 'admin/build/translate/edit/' . $data;
    $this->options['alter']['query'] = drupal_get_destination();

    return $text;
  }

}
