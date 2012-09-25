<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\join\Subquery.
 */

namespace Drupal\views\Plugin\views\join;
use Drupal\Core\Annotation\Plugin;

/**
 * Join handler for relationships that join with a subquery as the left field.
 * eg:
 *  LEFT JOIN node node_term_data ON ([YOUR SUBQUERY HERE]) = node_term_data.nid
 *
 * join definition
 *   same as Join class above, except:
 *   - left_query: The subquery to use in the left side of the join clause.
 *
 * @Plugin(
 *   id = "subquery"
 * )
 */
class Subquery extends JoinPluginBase {

  function construct($table = NULL, $left_table = NULL, $left_field = NULL, $field = NULL, $extra = array(), $type = 'LEFT') {
    parent::construct($table, $left_table, $left_field, $field, $extra, $type);
    $this->left_query = $this->definition['left_query'];
  }

  /**
   * Build the SQL for the join this object represents.
   *
   * @param $select_query
   *   An implementation of SelectQueryInterface.
   * @param $table
   *   The base table to join.
   * @param $view_query
   *   The source query, implementation of views_plugin_query.
   * @return
   *
   */
  public function buildJoin($select_query, $table, $view_query) {
    if (empty($this->definition['table formula'])) {
      $right_table = "{" . $this->table . "}";
    }
    else {
      $right_table = $this->definition['table formula'];
    }

    // Add our join condition, using a subquery on the left instead of a field.
    $condition = "($this->left_query) = $table[alias].$this->field";
    $arguments = array();

    // Tack on the extra.
    // This is just copied verbatim from the parent class, which itself has a bug: http://drupal.org/node/1118100
    if (isset($this->extra)) {
      if (is_array($this->extra)) {
        $extras = array();
        foreach ($this->extra as $info) {
          $extra = '';
          // Figure out the table name. Remember, only use aliases provided
          // if at all possible.
          $join_table = '';
          if (!array_key_exists('table', $info)) {
            $join_table = $table['alias'] . '.';
          }
          elseif (isset($info['table'])) {
            $join_table = $info['table'] . '.';
          }

          $placeholder = ':views_join_condition_' . $select_query->nextPlaceholder();

          if (is_array($info['value'])) {
            $operator = !empty($info['operator']) ? $info['operator'] : 'IN';
            // Transform from IN() notation to = notation if just one value.
            if (count($info['value']) == 1) {
              $info['value'] = array_shift($info['value']);
              $operator = $operator == 'NOT IN' ? '!=' : '=';
            }
          }
          else {
            $operator = !empty($info['operator']) ? $info['operator'] : '=';
          }

          $extras[] = "$join_table$info[field] $operator $placeholder";
          $arguments[$placeholder] = $info['value'];
        }

        if ($extras) {
          if (count($extras) == 1) {
            $condition .= ' AND ' . array_shift($extras);
          }
          else {
            $condition .= ' AND (' . implode(' ' . $this->extraOperator . ' ', $extras) . ')';
          }
        }
      }
      elseif ($this->extra && is_string($this->extra)) {
        $condition .= " AND ($this->extra)";
      }
    }

    $select_query->addJoin($this->type, $right_table, $table['alias'], $condition, $arguments);
  }

}
