<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\row\View.
 */

namespace Views\node\Plugin\views\row;

use Drupal\views\ViewExecutable;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Plugin which performs a node_view on the resulting object.
 *
 * Most of the code on this object is in the theme function.
 *
 * @ingroup views_row_plugins
 *
 * @Plugin(
 *   id = "node",
 *   module = "node",
 *   title = @Translation("Content"),
 *   help = @Translation("Display the content with standard node view."),
 *   base = {"node"},
 *   type = "normal"
 * )
 */
class View extends RowPluginBase {

  // Basic properties that let the row style follow relationships.
  var $base_table = 'node';

  var $base_field = 'nid';

  // Stores the nodes loaded with pre_render.
  var $nodes = array();

  public function init(ViewExecutable $view, &$display, $options = NULL) {
    parent::init($view, $display, $options);
    // Handle existing views with the deprecated 'teaser' option.
    if (isset($this->options['teaser'])) {
      $this->options['build_mode'] = $this->options['teaser'] ? 'teaser' : 'full';
    }
    // Handle existing views which has used build_mode instead of view_mode.
    if (isset($this->options['build_mode'])) {
      $this->options['view_mode'] = $this->options['build_mode'];
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode'] = array('default' => 'teaser');
    $options['links'] = array('default' => TRUE, 'bool' => TRUE);
    $options['comments'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = $this->buildOptionsForm_summary_options();
    $form['view_mode'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('View mode'),
      '#default_value' => $this->options['view_mode'],
     );
    $form['links'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display links'),
      '#default_value' => $this->options['links'],
    );
    $form['comments'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display comments'),
      '#default_value' => $this->options['comments'],
    );
  }

  /**
   * Return the main options, which are shown in the summary title.
   */
  public function buildOptionsForm_summary_options() {
    $entity_info = entity_get_info('node');
    $options = array();
    if (!empty($entity_info['view modes'])) {
      foreach ($entity_info['view modes'] as $mode => $settings) {
        $options[$mode] = $settings['label'];
      }
    }
    if (empty($options)) {
      $options = array(
        'teaser' => t('Teaser'),
        'full' => t('Full content')
      );
    }

    return $options;
  }

  public function summaryTitle() {
    $options = $this->buildOptionsForm_summary_options();
    return check_plain($options[$this->options['view_mode']]);
  }

  function pre_render($values) {
    $nids = array();
    foreach ($values as $row) {
      $nids[] = $row->{$this->field_alias};
    }
    $this->nodes = node_load_multiple($nids);
  }

  function render($row) {
    if (isset($this->nodes[$row->{$this->field_alias}])) {
      $node = $this->nodes[$row->{$this->field_alias}];
      $node->view = $this->view;
      $build = node_view($node, $this->options['view_mode']);

      return drupal_render($build);
    }
  }

}
