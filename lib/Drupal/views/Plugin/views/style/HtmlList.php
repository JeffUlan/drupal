<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\style\List.
 */

namespace Drupal\views\Plugin\views\style;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @Plugin(
 *   id = "html_list",
 *   title = @Translation("HTML List"),
 *   help = @Translation("Displays rows as HTML list."),
 *   theme = "views_view_list",
 *   type = "normal",
 *   help_topic = "style-list"
 * )
 */
class HtmlList extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  public $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  public $usesRowClass = TRUE;

  /**
   * Set default options
   */
  function option_definition() {
    $options = parent::option_definition();

    $options['type'] = array('default' => 'ul');
    $options['class'] = array('default' => '');
    $options['wrapper_class'] = array('default' => 'item-list');

    return $options;
  }

  /**
   * Render the given style.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('List type'),
      '#options' => array('ul' => t('Unordered list'), 'ol' => t('Ordered list')),
      '#default_value' => $this->options['type'],
    );
    $form['wrapper_class'] = array(
      '#title' => t('Wrapper class'),
      '#description' => t('The class to provide on the wrapper, outside the list.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['wrapper_class'],
    );
    $form['class'] = array(
      '#title' => t('List class'),
      '#description' => t('The class to provide on the list element itself.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['class'],
    );
  }

}
