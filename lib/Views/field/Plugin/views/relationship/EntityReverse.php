<?php

/**
 * @file
 * Definition of Views\field\Plugin\views\relationship\EntityReverse.
 */

namespace Views\field\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * A relationship handlers which reverse entity references.
 *
 * @ingroup views_relationship_handlers
 *
 * @Plugin(
 *   id = "entity_reverse",
 *   module = "field"
 * )
 */
class EntityReverse extends RelationshipPluginBase  {

  public function init(&$view, &$options) {
    parent::init($view, $options);

    $this->field_info = field_info_field($this->definition['field_name']);
  }

  /**
   * Called to implement a relationship in a query.
   */
  public function query() {
    $this->ensureMyTable();
    // First, relate our base table to the current base table to the
    // field, using the base table's id field to the field's column.
    $views_data = views_fetch_data($this->table);
    $left_field = $views_data['table']['base']['field'];

    $first = array(
      'left_table' => $this->table_alias,
      'left_field' => $left_field,
      'table' => $this->definition['field table'],
      'field' => $this->definition['field field'],
    );
    if (!empty($this->options['required'])) {
      $first['type'] = 'INNER';
    }

    if (!empty($this->definition['join_extra'])) {
      $first['extra'] = $this->definition['join_extra'];
    }

    if (!empty($def['join_id'])) {
      $id = $def['join_id'];
    }
    else {
      $id = 'standard';
    }
    $first_join = views_get_plugin('join', $id);

    $first_join->definition = $first;
    $first_join->construct();
    $first_join->adjusted = TRUE;

    $this->first_alias = $this->query->add_table($this->definition['field table'], $this->relationship, $first_join);

    // Second, relate the field table to the entity specified using
    // the entity id on the field table and the entity's id field.
    $second = array(
      'left_table' => $this->first_alias,
      'left_field' => 'entity_id',
      'table' => $this->definition['base'],
      'field' => $this->definition['base field'],
    );

    if (!empty($this->options['required'])) {
      $second['type'] = 'INNER';
    }

    if (!empty($def['join_id'])) {
      $id = $def['join_id'];
    }
    else {
      $id = 'standard';
    }
    $second_join = views_get_plugin('join', $id);
    $second_join->definition = $second;
    $second_join->construct();
    $second_join->adjusted = TRUE;

    // use a short alias for this:
    $alias = $this->definition['field_name'] . '_' . $this->table;

    $this->alias = $this->query->add_relationship($alias, $second_join, $this->definition['base'], $this->relationship);
  }

}
