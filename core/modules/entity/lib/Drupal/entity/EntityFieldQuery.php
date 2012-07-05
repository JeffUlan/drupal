<?php

/**
 * @file
 * Definition of Drupal\entity\EntityFieldQuery.
 */

namespace Drupal\entity;

use Drupal\entity\EntityFieldQueryException;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\PagerSelectExtender;

/**
 * Retrieves entities matching a given set of conditions.
 *
 * This class allows finding entities based on entity properties (for example,
 * node->changed), field values, and generic entity meta data (bundle,
 * entity type, entity id, and revision ID). It is not possible to query across
 * multiple entity types. For example, there is no facility to find published
 * nodes written by users created in the last hour, as this would require
 * querying both node->status and user->created.
 *
 * Normally we would not want to have public properties on the object, as that
 * allows the object's state to become inconsistent too easily. However, this
 * class's standard use case involves primarily code that does need to have
 * direct access to the collected properties in order to handle alternate
 * execution routines. We therefore use public properties for simplicity. Note
 * that code that is simply creating and running a field query should still use
 * the appropriate methods to add conditions on the query.
 *
 * Storage engines are not required to support every type of query. By default,
 * an EntityFieldQueryException will be raised if an unsupported condition is
 * specified or if the query has field conditions or sorts that are stored in
 * different field storage engines. However, this logic can be overridden in
 * hook_entity_query().
 *
 * Also note that this query does not automatically respect entity access
 * restrictions. Node access control is performed by the SQL storage engine but
 * other storage engines might not do this.
 */
class EntityFieldQuery {

  /**
   * Indicates that both deleted and non-deleted fields should be returned.
   *
   * @see Drupal\entity\EntityFieldQuery::deleted()
   */
  const RETURN_ALL = NULL;

  /**
   * TRUE if the query has already been altered, FALSE if it hasn't.
   *
   * Used in alter hooks to check for cloned queries that have already been
   * altered prior to the clone (for example, the pager count query).
   *
   * @var boolean
   */
  public $altered = FALSE;

  /**
   * Associative array of entity-generic metadata conditions.
   *
   * @var array
   *
   * @see Drupal\entity\EntityFieldQuery::entityCondition()
   */
  public $entityConditions = array();

  /**
   * List of field conditions.
   *
   * @var array
   *
   * @see Drupal\entity\EntityFieldQuery::fieldCondition()
   */
  public $fieldConditions = array();

  /**
   * List of field meta conditions (language and delta).
   *
   * Field conditions operate on columns specified by hook_field_schema(),
   * the meta conditions operate on columns added by the system: delta
   * and language. These can not be mixed with the field conditions because
   * field columns can have any name including delta and language.
   *
   * @var array
   *
   * @see Drupal\entity\EntityFieldQuery::fieldLanguageCondition()
   * @see Drupal\entity\EntityFieldQuery::fieldDeltaCondition()
   */
  public $fieldMetaConditions = array();

  /**
   * List of property conditions.
   *
   * @var array
   *
   * @see Drupal\entity\EntityFieldQuery::propertyCondition()
   */
  public $propertyConditions = array();

  /**
   * List of order clauses.
   *
   * @var array
   */
  public $order = array();

  /**
   * The query range.
   *
   * @var array
   *
   * @see Drupal\entity\EntityFieldQuery::range()
   */
  public $range = array();

  /**
   * The query pager data.
   *
   * @var array
   *
   * @see Drupal\entity\EntityFieldQuery::pager()
   */
  public $pager = array();

  /**
   * Query behavior for deleted data.
   *
   * TRUE to return only deleted data, FALSE to return only non-deleted data,
   * EntityFieldQuery::RETURN_ALL to return everything.
   *
   * @see Drupal\entity\EntityFieldQuery::deleted()
   */
  public $deleted = FALSE;

  /**
   * A list of field arrays used.
   *
   * Field names passed to EntityFieldQuery::fieldCondition() and
   * EntityFieldQuery::fieldOrderBy() are run through field_info_field() before
   * stored in this array. This way, the elements of this array are field
   * arrays.
   *
   * @var array
   */
  public $fields = array();

  /**
   * TRUE if this is a count query, FALSE if it isn't.
   *
   * @var boolean
   */
  public $count = FALSE;

  /**
   * Flag indicating whether this is querying current or all revisions.
   *
   * @var int
   *
   * @see Drupal\entity\EntityFieldQuery::age()
   */
  public $age = FIELD_LOAD_CURRENT;

  /**
   * A list of the tags added to this query.
   *
   * @var array
   *
   * @see Drupal\entity\EntityFieldQuery::addTag()
   */
  public $tags = array();

  /**
   * A list of metadata added to this query.
   *
   * @var array
   *
   * @see Drupal\entity\EntityFieldQuery::addMetaData()
   */
  public $metaData = array();

  /**
   * The ordered results.
   *
   * @var array
   *
   * @see Drupal\entity\EntityFieldQuery::execute().
   */
  public $orderedResults = array();

  /**
   * The method executing the query, if it is overriding the default.
   *
   * @var string
   *
   * @see Drupal\entity\EntityFieldQuery::execute()
   */
  public $executeCallback = '';

  /**
   * Adds a condition on entity-generic metadata.
   *
   * If the overall query contains only entity conditions or ordering, or if
   * there are property conditions, then specifying the entity type is
   * mandatory. If there are field conditions or ordering but no property
   * conditions or ordering, then specifying an entity type is optional. While
   * the field storage engine might support field conditions on more than one
   * entity type, there is no way to query across multiple entity base tables by
   * default. To specify the entity type, pass in 'entity_type' for $name,
   * the type as a string for $value, and no $operator (it's disregarded).
   *
   * 'bundle', 'revision_id' and 'entity_id' have no such restrictions.
   *
   * Note: The "comment" and "taxonomy_term" entity types don't support bundle
   * conditions. For "taxonomy_term", propertyCondition('vid') can be used
   * instead.
   *
   * @param $name
   *   'entity_type', 'bundle', 'revision_id' or 'entity_id'.
   * @param $value
   *   The value for $name. In most cases, this is a scalar. For more complex
   *   options, it is an array. The meaning of each element in the array is
   *   dependent on $operator.
   * @param $operator
   *   Possible values:
   *   - '=', '<>', '>', '>=', '<', '<=', 'STARTS_WITH', 'CONTAINS': These
   *     operators expect $value to be a literal of the same type as the
   *     column.
   *   - 'IN', 'NOT IN': These operators expect $value to be an array of
   *     literals of the same type as the column.
   *   - 'BETWEEN': This operator expects $value to be an array of two literals
   *     of the same type as the column.
   *   The operator can be omitted, and will default to 'IN' if the value is an
   *   array, or to '=' otherwise.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function entityCondition($name, $value, $operator = NULL) {
    $this->entityConditions[$name] = array(
      'value' => $value,
      'operator' => $operator,
    );
    return $this;
  }

  /**
   * Adds a condition on field values.
   *
   * Note that entities with empty field values will be excluded from the
   * EntityFieldQuery results when using this method.
   *
   * @param $field
   *   Either a field name or a field array.
   * @param $column
   *   The column that should hold the value to be matched.
   * @param $value
   *   The value to test the column value against.
   * @param $operator
   *   The operator to be used to test the given value.
   * @param $delta_group
   *   An arbitrary identifier: conditions in the same group must have the same
   *   $delta_group.
   * @param $langcode_group
   *   An arbitrary identifier: conditions in the same group must have the same
   *   $langcode_group.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   *
   * @see Drupal\entity\EntityFieldQuery::addFieldCondition()
   * @see Drupal\entity\EntityFieldQuery::deleted()
   */
  public function fieldCondition($field, $column = NULL, $value = NULL, $operator = NULL, $delta_group = NULL, $langcode_group = NULL) {
    return $this->addFieldCondition($this->fieldConditions, $field, $column, $value, $operator, $delta_group, $langcode_group);
  }

  /**
   * Adds a condition on the field language column.
   *
   * @param $field
   *   Either a field name or a field array.
   * @param $value
   *   The value to test the column value against.
   * @param $operator
   *   The operator to be used to test the given value.
   * @param $delta_group
   *   An arbitrary identifier: conditions in the same group must have the same
   *   $delta_group.
   * @param $langcode_group
   *   An arbitrary identifier: conditions in the same group must have the same
   *   $langcode_group.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   *
   * @see Drupal\entity\EntityFieldQuery::addFieldCondition()
   * @see Drupal\entity\EntityFieldQuery::deleted()
   */
  public function fieldLanguageCondition($field, $value = NULL, $operator = NULL, $delta_group = NULL, $langcode_group = NULL) {
    return $this->addFieldCondition($this->fieldMetaConditions, $field, 'langcode', $value, $operator, $delta_group, $langcode_group);
  }

  /**
   * Adds a condition on the field delta column.
   *
   * @param $field
   *   Either a field name or a field array.
   * @param $value
   *   The value to test the column value against.
   * @param $operator
   *   The operator to be used to test the given value.
   * @param $delta_group
   *   An arbitrary identifier: conditions in the same group must have the same
   *   $delta_group.
   * @param $langcode_group
   *   An arbitrary identifier: conditions in the same group must have the same
   *   $langcode_group.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   *
   * @see Drupal\entity\EntityFieldQuery::addFieldCondition()
   * @see Drupal\entity\EntityFieldQuery::deleted()
   */
  public function fieldDeltaCondition($field, $value = NULL, $operator = NULL, $delta_group = NULL, $langcode_group = NULL) {
    return $this->addFieldCondition($this->fieldMetaConditions, $field, 'delta', $value, $operator, $delta_group, $langcode_group);
  }

  /**
   * Adds the given condition to the proper condition array.
   *
   * @param $conditions
   *   A reference to an array of conditions.
   * @param $field
   *   Either a field name or a field array.
   * @param $column
   *   A column defined in the hook_field_schema() of this field. If this is
   *   omitted then the query will find only entities that have data in this
   *   field, using the entity and property conditions if there are any.
   * @param $value
   *   The value to test the column value against. In most cases, this is a
   *   scalar. For more complex options, it is an array. The meaning of each
   *   element in the array is dependent on $operator.
   * @param $operator
   *   Possible values:
   *   - '=', '<>', '>', '>=', '<', '<=', 'STARTS_WITH', 'CONTAINS': These
   *     operators expect $value to be a literal of the same type as the
   *     column.
   *   - 'IN', 'NOT IN': These operators expect $value to be an array of
   *     literals of the same type as the column.
   *   - 'BETWEEN': This operator expects $value to be an array of two literals
   *     of the same type as the column.
   *   The operator can be omitted, and will default to 'IN' if the value is an
   *   array, or to '=' otherwise.
   * @param $delta_group
   *   An arbitrary identifier: conditions in the same group must have the same
   *   $delta_group. For example, let's presume a multivalue field which has
   *   two columns, 'color' and 'shape', and for entity id 1, there are two
   *   values: red/square and blue/circle. Entity ID 1 does not have values
   *   corresponding to 'red circle', however if you pass 'red' and 'circle' as
   *   conditions, it will appear in the  results - by default queries will run
   *   against any combination of deltas. By passing the conditions with the
   *   same $delta_group it will ensure that only values attached to the same
   *   delta are matched, and entity 1 would then be excluded from the results.
   * @param $langcode_group
   *   An arbitrary identifier: conditions in the same group must have the same
   *   $langcode_group.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  protected function addFieldCondition(&$conditions, $field, $column = NULL, $value = NULL, $operator = NULL, $delta_group = NULL, $langcode_group = NULL) {
    if (is_scalar($field)) {
      $field_definition = field_info_field($field);
      if (empty($field_definition)) {
        throw new EntityFieldQueryException(t('Unknown field: @field_name', array('@field_name' => $field)));
      }
      $field = $field_definition;
    }
    // Ensure the same index is used for field conditions as for fields.
    $index = count($this->fields);
    $this->fields[$index] = $field;
    if (isset($column)) {
      $conditions[$index] = array(
        'field' => $field,
        'column' => $column,
        'value' => $value,
        'operator' => $operator,
        'delta_group' => $delta_group,
        'langcode_group' => $langcode_group,
      );
    }
    return $this;
  }

  /**
   * Adds a condition on an entity-specific property.
   *
   * An $entity_type must be specified by calling
   * EntityFieldCondition::entityCondition('entity_type', $entity_type) before
   * executing the query. Also, by default only entities stored in SQL are
   * supported; however, EntityFieldQuery::executeCallback can be set to handle
   * different entity storage.
   *
   * @param $column
   *   A column defined in the hook_schema() of the base table of the entity.
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar. For
   *   more complex options, it is an array. The meaning of each element in the
   *   array is dependent on $operator.
   * @param $operator
   *   Possible values:
   *   - '=', '<>', '>', '>=', '<', '<=', 'STARTS_WITH', 'CONTAINS': These
   *     operators expect $value to be a literal of the same type as the
   *     column.
   *   - 'IN', 'NOT IN': These operators expect $value to be an array of
   *     literals of the same type as the column.
   *   - 'BETWEEN': This operator expects $value to be an array of two literals
   *     of the same type as the column.
   *   The operator can be omitted, and will default to 'IN' if the value is an
   *   array, or to '=' otherwise.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function propertyCondition($column, $value, $operator = NULL) {
    $this->propertyConditions[] = array(
      'column' => $column,
      'value' => $value,
      'operator' => $operator,
    );
    return $this;
  }

  /**
   * Orders the result set by entity-generic metadata.
   *
   * If called multiple times, the query will order by each specified column in
   * the order this method is called.
   *
   * Note: The "comment" and "taxonomy_term" entity types don't support ordering
   * by bundle. For "taxonomy_term", propertyOrderBy('vid') can be used instead.
   *
   * @param $name
   *   'entity_type', 'bundle', 'revision_id' or 'entity_id'.
   * @param $direction
   *   The direction to sort. Legal values are "ASC" and "DESC".
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function entityOrderBy($name, $direction = 'ASC') {
    $this->order[] = array(
      'type' => 'entity',
      'specifier' => $name,
      'direction' => $direction,
    );
    return $this;
  }

  /**
   * Orders the result set by a given field column.
   *
   * If called multiple times, the query will order by each specified column in
   * the order this method is called. Note that entities with empty field
   * values will be excluded from the EntityFieldQuery results when using this
   * method.
   *
   * @param $field
   *   Either a field name or a field array.
   * @param $column
   *   A column defined in the hook_field_schema() of this field. entity_id and
   *   bundle can also be used.
   * @param $direction
   *   The direction to sort. Legal values are "ASC" and "DESC".
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function fieldOrderBy($field, $column, $direction = 'ASC') {
    if (is_scalar($field)) {
      $field_definition = field_info_field($field);
      if (empty($field_definition)) {
        throw new EntityFieldQueryException(t('Unknown field: @field_name', array('@field_name' => $field)));
      }
      $field = $field_definition;
    }
    // Save the index used for the new field, for later use in field storage.
    $index = count($this->fields);
    $this->fields[$index] = $field;
    $this->order[] = array(
      'type' => 'field',
      'specifier' => array(
        'field' => $field,
        'index' => $index,
        'column' => $column,
      ),
      'direction' => $direction,
    );
    return $this;
  }

  /**
   * Orders the result set by an entity-specific property.
   *
   * An $entity_type must be specified by calling
   * EntityFieldCondition::entityCondition('entity_type', $entity_type) before
   * executing the query.
   *
   * If called multiple times, the query will order by each specified column in
   * the order this method is called.
   *
   * @param $column
   *   The column on which to order.
   * @param $direction
   *   The direction to sort. Legal values are "ASC" and "DESC".
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function propertyOrderBy($column, $direction = 'ASC') {
    $this->order[] = array(
      'type' => 'property',
      'specifier' => $column,
      'direction' => $direction,
    );
    return $this;
  }

  /**
   * Sets the query to be a count query only.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function count() {
    $this->count = TRUE;
    return $this;
  }

  /**
   * Restricts a query to a given range in the result set.
   *
   * @param $start
   *   The first entity from the result set to return. If NULL, removes any
   *   range directives that are set.
   * @param $length
   *   The number of entities to return from the result set.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function range($start = NULL, $length = NULL) {
    $this->range = array(
      'start' => $start,
      'length' => $length,
    );
    return $this;
  }

  /**
   * Enables a pager for the query.
   *
   * @param $limit
   *   An integer specifying the number of elements per page.  If passed a false
   *   value (FALSE, 0, NULL), the pager is disabled.
   * @param $element
   *   An optional integer to distinguish between multiple pagers on one page.
   *   If not provided, one is automatically calculated.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function pager($limit = 10, $element = NULL) {
    if (!isset($element)) {
      $element = PagerSelectExtender::$maxElement++;
    }
    elseif ($element >= PagerSelectExtender::$maxElement) {
      PagerSelectExtender::$maxElement = $element + 1;
    }

    $this->pager = array(
      'limit' => $limit,
      'element' => $element,
    );
    return $this;
  }

  /**
   * Enables sortable tables for this query.
   *
   * @param $headers
   *   An EFQ Header array based on which the order clause is added to the
   *   query.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function tableSort(&$headers) {
    // If 'field' is not initialized, the header columns aren't clickable
    foreach ($headers as $key =>$header) {
      if (is_array($header) && isset($header['specifier'])) {
        $headers[$key]['field'] = '';
      }
    }

    $order = tablesort_get_order($headers);
    $direction = tablesort_get_sort($headers);
    foreach ($headers as $header) {
      if (is_array($header) && ($header['data'] == $order['name'])) {
        if ($header['type'] == 'field') {
          $this->fieldOrderBy($header['specifier']['field'], $header['specifier']['column'], $direction);
        }
        else {
          $header['direction'] = $direction;
          $this->order[] = $header;
        }
      }
    }

    return $this;
  }

  /**
   * Filters on the data being deleted.
   *
   * @param $deleted
   *   TRUE to only return deleted data, FALSE to return non-deleted data,
   *   EntityFieldQuery::RETURN_ALL to return everything. Defaults to FALSE.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function deleted($deleted = TRUE) {
    $this->deleted = $deleted;
    return $this;
  }

  /**
   * Queries the current or every revision.
   *
   * Note that this only affects field conditions. Property conditions always
   * apply to the current revision.
   * @TODO: Once revision tables have been cleaned up, revisit this.
   *
   * @param $age
   *   - FIELD_LOAD_CURRENT (default): Query the most recent revisions for all
   *     entities. The results will be keyed by entity type and entity ID.
   *   - FIELD_LOAD_REVISION: Query all revisions. The results will be keyed by
   *     entity type and entity revision ID.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function age($age) {
    $this->age = $age;
    return $this;
  }

  /**
   * Adds a tag to the query.
   *
   * Tags are strings that mark a query so that hook_query_alter() and
   * hook_query_TAG_alter() implementations may decide if they wish to alter
   * the query. A query may have any number of tags, and they must be valid PHP
   * identifiers (composed of letters, numbers, and underscores). For example,
   * queries involving nodes that will be displayed for a user need to add the
   * tag 'node_access', so that the node module can add access restrictions to
   * the query.
   *
   * If an entity field query has tags, it must also have an entity type
   * specified, because the alter hook will need the entity base table.
   *
   * @param string $tag
   *   The tag to add.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function addTag($tag) {
    $this->tags[$tag] = $tag;
    return $this;
  }

  /**
   * Adds additional metadata to the query.
   *
   * Sometimes a query may need to provide additional contextual data for the
   * alter hook. The alter hook implementations may then use that information
   * to decide if and how to take action.
   *
   * @param $key
   *   The unique identifier for this piece of metadata. Must be a string that
   *   follows the same rules as any other PHP identifier.
   * @param $object
   *   The additional data to add to the query. May be any valid PHP variable.
   *
   * @return Drupal\entity\EntityFieldQuery
   *   The called object.
   */
  public function addMetaData($key, $object) {
    $this->metaData[$key] = $object;
    return $this;
  }

  /**
   * Executes the query.
   *
   * After executing the query, $this->orderedResults will contain a list of
   * the same stub entities in the order returned by the query. This is only
   * relevant if there are multiple entity types in the returned value and
   * a field ordering was requested. In every other case, the returned value
   * contains everything necessary for processing.
   *
   * @return
   *   Either a number if count() was called or an array of associative arrays
   *   of stub entities. The outer array keys are entity types, and the inner
   *   array keys are the relevant ID. (In most cases this will be the entity
   *   ID. The only exception is when age=FIELD_LOAD_REVISION is used and field
   *   conditions or sorts are present -- in this case, the key will be the
   *   revision ID.) The entity type will only exist in the outer array if
   *   results were found. The inner array values are always stub entities, as
   *   returned by entity_create_stub_entity(). To traverse the returned array:
   *   @code
   *     foreach ($query->execute() as $entity_type => $entities) {
   *       foreach ($entities as $entity_id => $entity) {
   *   @endcode
   *   Note if the entity type is known, then the following snippet will load
   *   the entities found:
   *   @code
   *     $result = $query->execute();
   *     if (!empty($result[$my_type])) {
   *       $entities = entity_load_multiple($my_type, array_keys($result[$my_type]));
   *     }
   *   @endcode
   */
  public function execute() {
    // Give a chance to other modules to alter the query.
    drupal_alter('entity_query', $this);
    $this->altered = TRUE;

    // Initialize the pager.
    $this->initializePager();

    // Execute the query using the correct callback.
    $result = call_user_func($this->queryCallback(), $this);

    return $result;
  }

  /**
   * Determines the query callback to use for this entity query.
   *
   * @return
   *   A callback that can be used with call_user_func().
   */
  public function queryCallback() {
    // Use the override from $this->executeCallback. It can be set either
    // while building the query, or using hook_entity_query_alter().
    if (function_exists($this->executeCallback)) {
      return $this->executeCallback;
    }
    // If there are no field conditions and sorts, and no execute callback
    // then we default to querying entity tables in SQL.
    if (empty($this->fields)) {
      return array($this, 'propertyQuery');
    }
    // If no override, find the storage engine to be used.
    foreach ($this->fields as $field) {
      if (!isset($storage)) {
        $storage = $field['storage']['module'];
      }
      elseif ($storage != $field['storage']['module']) {
        throw new EntityFieldQueryException(t("Can't handle more than one field storage engine"));
      }
    }
    if ($storage) {
      // Use hook_field_storage_query() from the field storage.
      return $storage . '_field_storage_query';
    }
    else {
      throw new EntityFieldQueryException(t("Field storage engine not found."));
    }
  }

  /**
   * Queries entity tables in SQL for property conditions and sorts.
   *
   * This method is only used if there are no field conditions and sorts.
   *
   * @return
   *   See EntityFieldQuery::execute().
   */
  protected function propertyQuery() {
    if (empty($this->entityConditions['entity_type'])) {
      throw new EntityFieldQueryException(t('For this query an entity type must be specified.'));
    }
    $entity_type = $this->entityConditions['entity_type']['value'];
    $entity_info = entity_get_info($entity_type);
    if (empty($entity_info['base table'])) {
      throw new EntityFieldQueryException(t('Entity %entity has no base table.', array('%entity' => $entity_type)));
    }
    $base_table = $entity_info['base table'];
    $base_table_schema = drupal_get_schema($base_table);
    $select_query = db_select($base_table);
    $select_query->addExpression(':entity_type', 'entity_type', array(':entity_type' => $entity_type));
    // Process the property conditions.
    foreach ($this->propertyConditions as $property_condition) {
      $this->addCondition($select_query, "$base_table." . $property_condition['column'], $property_condition);
    }
    // Process the four possible entity condition.
    // The id field is always present in entity keys.
    $sql_field = $entity_info['entity keys']['id'];
    $id_map['entity_id'] = $sql_field;
    $select_query->addField($base_table, $sql_field, 'entity_id');
    if (isset($this->entityConditions['entity_id'])) {
      $this->addCondition($select_query, $sql_field, $this->entityConditions['entity_id']);
    }

    // If there is a revision key defined, use it.
    if (!empty($entity_info['entity keys']['revision'])) {
      $sql_field = $entity_info['entity keys']['revision'];
      $select_query->addField($base_table, $sql_field, 'revision_id');
      if (isset($this->entityConditions['revision_id'])) {
        $this->addCondition($select_query, $sql_field, $this->entityConditions['revision_id']);
      }
    }
    else {
      $sql_field = 'revision_id';
      $select_query->addExpression('NULL', 'revision_id');
    }
    $id_map['revision_id'] = $sql_field;

    // Handle bundles.
    if (!empty($entity_info['entity keys']['bundle'])) {
      $sql_field = $entity_info['entity keys']['bundle'];
      $having = FALSE;

      if (!empty($base_table_schema['fields'][$sql_field])) {
        $select_query->addField($base_table, $sql_field, 'bundle');
      }
    }
    else {
      $sql_field = 'bundle';
      $select_query->addExpression(':bundle', 'bundle', array(':bundle' => $entity_type));
      $having = TRUE;
    }
    $id_map['bundle'] = $sql_field;
    if (isset($this->entityConditions['bundle'])) {
      $this->addCondition($select_query, $sql_field, $this->entityConditions['bundle'], $having);
    }

    // Order the query.
    foreach ($this->order as $order) {
      if ($order['type'] == 'entity') {
        $key = $order['specifier'];
        if (!isset($id_map[$key])) {
          throw new EntityFieldQueryException(t('Do not know how to order on @key for @entity_type', array('@key' => $key, '@entity_type' => $entity_type)));
        }
        $select_query->orderBy($id_map[$key], $order['direction']);
      }
      elseif ($order['type'] == 'property') {
        $select_query->orderBy("$base_table." . $order['specifier'], $order['direction']);
      }
    }

    return $this->finishQuery($select_query);
  }

  /**
   * Gets the total number of results and initialize a pager for the query.
   *
   * The pager can be disabled by either setting the pager limit to 0, or by
   * setting this query to be a count query.
   */
  function initializePager() {
    if ($this->pager && !empty($this->pager['limit']) && !$this->count) {
      $page = pager_find_page($this->pager['element']);
      $count_query = clone $this;
      $this->pager['total'] = $count_query->count()->execute();
      $this->pager['start'] = $page * $this->pager['limit'];
      pager_default_initialize($this->pager['total'], $this->pager['limit'], $this->pager['element']);
      $this->range($this->pager['start'], $this->pager['limit']);
    }
  }

  /**
   * Finishes the query.
   *
   * Adds tags, metaData, range and returns the requested list or count.
   *
   * @param SelectQuery $select_query
   *   A SelectQuery which has entity_type, entity_id, revision_id and bundle
   *   fields added.
   * @param $id_key
   *   Which field's values to use as the returned array keys.
   *
   * @return
   *   See EntityFieldQuery::execute().
   */
  function finishQuery($select_query, $id_key = 'entity_id') {
    foreach ($this->tags as $tag) {
      $select_query->addTag($tag);
    }
    foreach ($this->metaData as $key => $object) {
      $select_query->addMetaData($key, $object);
    }
    $select_query->addMetaData('entity_field_query', $this);
    if ($this->range) {
      $select_query->range($this->range['start'], $this->range['length']);
    }
    if ($this->count) {
      return $select_query->countQuery()->execute()->fetchField();
    }
    $return = array();
    foreach ($select_query->execute() as $partial_entity) {
      $bundle = isset($partial_entity->bundle) ? $partial_entity->bundle : NULL;
      $entity = entity_create_stub_entity($partial_entity->entity_type, array($partial_entity->entity_id, $partial_entity->revision_id, $bundle));
      $return[$partial_entity->entity_type][$partial_entity->$id_key] = $entity;
      $this->ordered_results[] = $partial_entity;
    }
    return $return;
  }

  /**
   * Adds a condition to an already built SelectQuery (internal function).
   *
   * This is a helper for hook_entity_query() and hook_field_storage_query().
   *
   * @param SelectQuery $select_query
   *   A SelectQuery object.
   * @param $sql_field
   *   The name of the field.
   * @param $condition
   *   A condition as described in EntityFieldQuery::fieldCondition() and
   *   EntityFieldQuery::entityCondition().
   * @param $having
   *   HAVING or WHERE. This is necessary because SQL can't handle WHERE
   *   conditions on aliased columns.
   */
  public function addCondition(Select $select_query, $sql_field, $condition, $having = FALSE) {
    $method = $having ? 'havingCondition' : 'condition';
    $like_prefix = '';
    switch ($condition['operator']) {
      case 'CONTAINS':
        $like_prefix = '%';
      case 'STARTS_WITH':
        $select_query->$method($sql_field, $like_prefix . db_like($condition['value']) . '%', 'LIKE');
        break;
      default:
        $select_query->$method($sql_field, $condition['value'], $condition['operator']);
    }
  }

}
