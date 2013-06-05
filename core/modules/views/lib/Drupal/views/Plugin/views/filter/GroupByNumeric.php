<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\filter\GroupByNumeric.
 */

namespace Drupal\views\Plugin\views\filter;

use Drupal\Component\Annotation\PluginID;

/**
 * Simple filter to handle greater than/less than filters
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("groupby_numeric")
 */
class GroupByNumeric extends Numeric {

  public function query() {
    $this->ensureMyTable();
    $field = $this->getField();

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }
  protected function opBetween($field) {
    $placeholder_min = $this->placeholder();
    $placeholder_max = $this->placeholder();
    if ($this->operator == 'between') {
      $this->query->addHavingExpression($this->options['group'], "$field >= $placeholder_min", array($placeholder_min => $this->value['min']));
      $this->query->addHavingExpression($this->options['group'], "$field <= $placeholder_max", array($placeholder_max => $this->value['max']));
    }
    else {
      $this->query->addHavingExpression($this->options['group'], "$field <= $placeholder_min OR $field >= $placeholder_max", array($placeholder_min => $this->value['min'], $placeholder_max => $this->value['max']));
    }
  }

  function op_simple($field) {
    $placeholder = $this->placeholder();
    $this->query->addHavingExpression($this->options['group'], "$field $this->operator $placeholder", array($placeholder => $this->value['value']));
  }

  function op_empty($field) {
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->addHavingExpression($this->options['group'], "$field $operator");
  }

  public function adminLabel($short = FALSE) {
    return $this->getField(parent::adminLabel($short));
  }

  public function canGroup() { return FALSE; }

}
