<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\TimeInterval.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;

/**
 * A handler to provide proper displays for time intervals.
 *
 * @ingroup views_field_handlers
 */

/**
 * @plugin(
 *   id = "time_interval"
 * )
 */
class TimeInterval extends FieldPluginBase {
  function option_definition() {
    $options = parent::option_definition();

    $options['granularity'] = array('default' => 2);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['granularity'] = array(
      '#type' => 'textfield',
      '#title' => t('Granularity'),
      '#description' => t('How many different units to display in the string.'),
      '#default_value' => $this->options['granularity'],
    );
  }

  function render($values) {
    $value = $values->{$this->field_alias};
    return format_interval($value, isset($this->options['granularity']) ? $this->options['granularity'] : 2);
  }
}
