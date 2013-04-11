<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\View.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Component\Annotation\PluginID;

/**
 * Views area handlers. Insert a view inside of an area.
 *
 * @ingroup views_area_handlers
 *
 * @PluginID("view")
 */
class View extends AreaPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_to_insert'] = array('default' => '');
    $options['inherit_arguments'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $view_display = $this->view->storage->id() . ':' . $this->view->current_display;

    $options = array('' => t('-Select-'));
    $options += views_get_views_as_options(FALSE, 'all', $view_display, FALSE, TRUE);
    $form['view_to_insert'] = array(
      '#type' => 'select',
      '#title' => t('View to insert'),
      '#default_value' => $this->options['view_to_insert'],
      '#description' => t('The view to insert into this area.'),
      '#options' => $options,
    );

    $form['inherit_arguments'] = array(
      '#type' => 'checkbox',
      '#title' => t('Inherit contextual filters'),
      '#default_value' => $this->options['inherit_arguments'],
      '#description' => t('If checked, this view will receive the same contextual filters as its parent.'),
    );
  }

  /**
   * Implements \Drupal\views\Plugin\views\area\AreaPluginBase::render().
   */
  function render($empty = FALSE) {
    if (!empty($this->options['view_to_insert'])) {
      list($view_name, $display_id) = explode(':', $this->options['view_to_insert']);

      $view = views_get_view($view_name);
      if (empty($view) || !$view->access($display_id)) {
        return array();
      }
      $view->setDisplay($display_id);

      // Avoid recursion
      $view->parent_views += $this->view->parent_views;
      $view->parent_views[] = "$view_name:$display_id";

      // Check if the view is part of the parent views of this view
      $search = "$view_name:$display_id";
      if (in_array($search, $this->view->parent_views)) {
        drupal_set_message(t("Recursion detected in view @view display @display.", array('@view' => $view_name, '@display' => $display_id)), 'error');
      }
      else {
        if (!empty($this->options['inherit_arguments']) && !empty($this->view->args)) {
          return $view->preview($display_id, $this->view->args);
        }
        else {
          return $view->preview($display_id);
        }
      }
    }
    return array();
  }

}
