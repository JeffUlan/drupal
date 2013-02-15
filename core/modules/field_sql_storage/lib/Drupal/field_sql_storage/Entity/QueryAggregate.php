<?php

/**
 * @file
 * Contains \Drupal\field_sql_storage\Entity\QueryAggregate.
 */

namespace Drupal\field_sql_storage\Entity;

use Drupal\Core\Entity\Query\QueryAggregateInterface;

/**
 * The SQL storage entity query aggregate class.
 */
class QueryAggregate extends Query implements QueryAggregateInterface {

  /**
   * Stores the sql expressions used to build the sql query.
   *
   * @var array
   *   An array of expressions.
   */
  protected $sqlExpressions = array();

  /**
   * Implements \Drupal\Core\Entity\Query\QueryAggregateInterface::execute().
   */
  public function execute() {
    return $this
      ->prepare()
      ->addAggregate()
      ->compile()
      ->compileAggregate()
      ->addGroupBy()
      ->addSort()
      ->addSortAggregate()
      ->finish()
      ->result();
  }

  /**
   * Overrides \Drupal\field_sql_storage\Entity::prepare().
   */
  public function prepare() {
    parent::prepare();
    // Throw away the id fields.
    $this->sqlFields = array();
    return $this;
  }

  /**
   * Implements \Drupal\Core\Entity\Query\QueryAggregateInterface::conditionAggregateGroupFactory().
   */
  public function conditionAggregateGroupFactory($conjunction = 'AND') {
    return new ConditionAggregate($conjunction);
  }

  /**
   * Overrides \Drupal\Core\Entity\QueryBase::exists().
   */
  public function existsAggregate($field, $function, $langcode = NULL) {
    return $this->conditionAggregate->exists($field, $function, $langcode);
  }

  /**
   * Overrides \Drupal\Core\Entity\QueryBase::notExists().
   */
  public function notExistsAggregate($field, $function, $langcode = NULL) {
    return $this->conditionAggregate->notExists($field, $function, $langcode);
  }


  /**
   * Adds the aggregations to the query.
   *
   * @return \Drupal\field_sql_storage\Entity\QueryAggregate
   *   Returns the called object.
   */
  protected function addAggregate() {
    if ($this->aggregate) {
      foreach ($this->aggregate as $aggregate) {
        $sql_field = $this->getSqlField($aggregate['field'], $aggregate['langcode']);
        $this->sqlExpressions[$aggregate['alias']] = $aggregate['function'] . "($sql_field)";
      }
    }
    return $this;
  }

  /**
   * Builds the aggregation conditions part of the query.
   *
   * @return \Drupal\field_sql_storage\Entity\QueryAggregate
   *   Returns the called object.
   */
  protected function compileAggregate() {
    $this->conditionAggregate->compile($this->sqlQuery);
    return $this;
  }

  /**
   * Adds the groupby values to the actual query.
   *
   * @return \Drupal\field_sql_storage\Entity\QueryAggregate
   *   Returns the called object.
   */
  protected function addGroupBy() {
    foreach ($this->groupBy as $group_by) {
      $field = $group_by['field'];
      $sql_field = $this->getSqlField($field, $group_by['langcode']);
      $this->sqlGroupBy[$sql_field] = $sql_field;
      list($table, $real_sql_field) = explode('.', $sql_field);
      $this->sqlFields[$sql_field] = array($table, $real_sql_field, $this->createSqlAlias($field, $real_sql_field));
    }

    return $this;
  }

  /**
   * Builds the aggregation sort part of the query.
   *
   * @return \Drupal\field_sql_storage\Entity\QueryAggregate
   *   Returns the called object.
   */
  protected function addSortAggregate() {
    if(!$this->count) {
      foreach ($this->sortAggregate as $alias => $sort) {
        $this->sqlQuery->orderBy($this->sqlExpressions[$alias], $sort['direction']);
      }
    }
    return $this;
  }


  /**
   * Overrides \Drupal\field_sql_storage\Entity\Query::finish().
   *
   * Adds the sql expressions to the query.
   */
  protected function finish() {
    foreach ($this->sqlExpressions as $alias => $expression) {
      $this->sqlQuery->addExpression($expression, $alias);
    }
    return parent::finish();
  }

  /**
   * Builds a sql alias as expected in the result.
   *
   * @param string $field
   *   The field as passed in by the caller.
   * @param string $sql_field
   *   The sql field as returned by getSqlField.
   * @return string
   *   The SQL alias expected in the return value. The dots in $sql_field are
   *   replaced with underscores and if a default fallback to .value happened,
   *   the _value is stripped.
   */
  function createSqlAlias($field, $sql_field) {
    $alias = str_replace('.', '_', $sql_field);
    // If the alias contains of field_*_value remove the _value at the end.
    if (substr($alias, 0, 6) === 'field_' && substr($field, -6) !== '_value' && substr($alias, -6) === '_value') {
      $alias = substr($alias, 0, -6);
    }
    return $alias;
  }

  /**
   * Overrides \Drupal\field_sql_storage\Entity\Query::result().
   *
   * @return array|int
   *   Returns the aggregated result, or a number if it's a count query.
   */
  protected function result() {
    if ($this->count) {
      return parent::result();
    }
    $return = array();
    foreach ($this->sqlQuery->execute() as $row) {
      $return[] = (array)$row;
    }
    return $return;
  }

}
