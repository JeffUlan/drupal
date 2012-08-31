<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\style\Grid.
 */

namespace Drupal\views\Plugin\views\style;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Style plugin to render each item in a grid cell.
 *
 * @ingroup views_style_plugins
 *
 * @Plugin(
 *   id = "grid",
 *   title = @Translation("Grid"),
 *   help = @Translation("Displays rows in a grid."),
 *   theme = "views_view_grid",
 *   type = "normal"
 * )
 */
class Grid extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Set default options
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['columns'] = array('default' => '4');
    $options['alignment'] = array('default' => 'horizontal');
    $options['fill_single_line'] = array('default' => TRUE, 'bool' => TRUE);
    $options['summary'] = array('default' => '');

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['columns'] = array(
      '#type' => 'number',
      '#title' => t('Number of columns'),
      '#default_value' => $this->options['columns'],
      '#required' => TRUE,
      '#min' => 0,
    );
    $form['alignment'] = array(
      '#type' => 'radios',
      '#title' => t('Alignment'),
      '#options' => array('horizontal' => t('Horizontal'), 'vertical' => t('Vertical')),
      '#default_value' => $this->options['alignment'],
      '#description' => t('Horizontal alignment will place items starting in the upper left and moving right. Vertical alignment will place items starting in the upper left and moving down.'),
    );

    $form['fill_single_line'] = array(
      '#type' => 'checkbox',
      '#title' => t('Fill up single line'),
      '#description' => t('If you disable this option, a grid with only one row will have the same number of table cells (<TD>) as items. Disabling it can cause problems with your CSS.'),
      '#default_value' => !empty($this->options['fill_single_line']),
    );

    $form['summary'] = array(
      '#type' => 'textfield',
      '#title' => t('Table summary'),
      '#description' => t('This value will be displayed as table-summary attribute in the html. Set this for better accessiblity of your site.'),
      '#default_value' => $this->options['summary'],
    );
  }

}
