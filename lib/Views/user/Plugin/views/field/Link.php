<?php

/**
 * @file
 * Definition of Views\user\Plugin\views\field\Link.
 */

namespace Views\user\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link to the user.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "user_link",
 *   module = "user"
 * )
 */
class Link extends FieldPluginBase {

  public function construct() {
    parent::construct();
    $this->additional_fields['uid'] = 'uid';
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  // An example of field level access control.
  function access() {
    return user_access('access user profiles');
  }

  public function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $value = $this->get_value($values, 'uid');
    return $this->render_link($this->sanitize_value($value), $values);
  }

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('view');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "user/" . $data;

    return $text;
  }

}
