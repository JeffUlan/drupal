<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\views\field\Url.
 */

namespace Drupal\views\Plugins\views\field;

/**
 * Field handler to provide simple renderer that turns a URL into a clickable link.
 *
 * @ingroup views_field_handlers
 */
class Url extends FieldPluginBase {
  function option_definition() {
    $options = parent::option_definition();

    $options['display_as_link'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide link to the page being visited.
   */
  function options_form(&$form, &$form_state) {
    $form['display_as_link'] = array(
      '#title' => t('Display as link'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['display_as_link']),
    );
    parent::options_form($form, $form_state);
  }

  function render($values) {
    $value = $this->get_value($values);
    if (!empty($this->options['display_as_link'])) {
      return l($this->sanitize_value($value), $value, array('html' => TRUE));
    }
    else {
      return $this->sanitize_value($value, 'url');
    }
  }
}
