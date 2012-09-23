<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\argument\CreatedWeek.
 */

namespace Views\node\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for a week.
 *
 * @Plugin(
 *   id = "node_created_week",
 *   arg_format = "w",
 *   module = "node"
 * )
 */
class CreatedWeek extends Date {

  /**
   * Overrides Drupal\views\Plugin\views\argument\Formula::get_formula().
   */
  function get_formula() {
    $this->formula = views_date_sql_extract('WEEK', "***table***.$this->realField");
    return parent::get_formula();
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $created = $data->{$this->name_alias};
    return t('Week @week', array('@week' => $created));
  }

}
