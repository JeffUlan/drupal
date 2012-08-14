<?php

/**
 * @file
 * Definition of views_handler_argument_term_node_tid.
 */

/**
 * Allow taxonomy term ID(s) as argument.
 *
 * @ingroup views_argument_handlers
 */

namespace Views\taxonomy\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\ManyToOne;

/**
 * @Plugin(
 *   id = "taxonomy_index_tid",
 *   module = "taxonomy"
 * )
 */
class IndexTid extends ManyToOne {
  function option_definition() {
    $options = parent::option_definition();
    $options['set_breadcrumb'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['set_breadcrumb'] = array(
      '#type' => 'checkbox',
      '#title' => t("Set the breadcrumb for the term parents"),
      '#description' => t('If selected, the breadcrumb trail will include all parent terms, each one linking to this view. Note that this only works if just one term was received.'),
      '#default_value' => !empty($this->options['set_breadcrumb']),
    );
  }

  function set_breadcrumb(&$breadcrumb) {
    if (empty($this->options['set_breadcrumb']) || !is_numeric($this->argument)) {
      return;
    }

    return views_taxonomy_set_breadcrumb($breadcrumb, $this);
  }

  function title_query() {
    $titles = array();
    $result = db_select('taxonomy_term_data', 'td')
      ->fields('td', array('name'))
      ->condition('td.tid', $this->value)
      ->execute();
    foreach ($result as $term) {
      $titles[] = check_plain($term->name);
    }
    return $titles;
  }
}
