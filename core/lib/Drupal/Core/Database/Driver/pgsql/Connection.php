<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Driver\pgsql\Connection.
 */

namespace Drupal\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\Database\DatabaseNotFoundException;

/**
 * @addtogroup database
 * @{
 */

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends DatabaseConnection {

  /**
   * The name by which to obtain a lock for retrieve the next insert id.
   */
  const POSTGRESQL_NEXTID_LOCK = 1000;

  /**
   * Error code for "Unknown database" error.
   */
  const DATABASE_NOT_FOUND = 7;

  /**
   * The list of PostgreSQL reserved key words.
   *
   * @see http://www.postgresql.org/docs/9.4/static/sql-keywords-appendix.html
   */
  protected $postgresqlReservedKeyWords = ['all', 'analyse', 'analyze', 'and',
  'any', 'array', 'as', 'asc', 'asymmetric', 'authorization', 'binary', 'both',
  'case', 'cast', 'check', 'collate', 'collation', 'column', 'concurrently',
  'constraint', 'create', 'cross', 'current_catalog', 'current_date',
  'current_role', 'current_schema', 'current_time', 'current_timestamp',
  'current_user', 'default', 'deferrable', 'desc', 'distinct', 'do', 'else',
  'end', 'except', 'false', 'fetch', 'for', 'foreign', 'freeze', 'from', 'full',
  'grant', 'group', 'having', 'ilike', 'in', 'initially', 'inner', 'intersect',
  'into', 'is', 'isnull', 'join', 'lateral', 'leading', 'left', 'like', 'limit',
  'localtime', 'localtimestamp', 'natural', 'not', 'notnull', 'null', 'offset',
  'on', 'only', 'or', 'order', 'outer', 'over', 'overlaps', 'placing',
  'primary', 'references', 'returning', 'right', 'select', 'session_user',
  'similar', 'some', 'symmetric', 'table', 'then', 'to', 'trailing', 'true',
  'union', 'unique', 'user', 'using', 'variadic', 'verbose', 'when', 'where',
  'window', 'with'];

  /**
   * Constructs a connection object.
   */
  public function __construct(\PDO $connection, array $connection_options) {
    parent::__construct($connection, $connection_options);

    // This driver defaults to transaction support, except if explicitly passed FALSE.
    $this->transactionSupport = !isset($connection_options['transactions']) || ($connection_options['transactions'] !== FALSE);

    // Transactional DDL is always available in PostgreSQL,
    // but we'll only enable it if standard transactions are.
    $this->transactionalDDLSupport = $this->transactionSupport;

    $this->connectionOptions = $connection_options;

    // Force PostgreSQL to use the UTF-8 character set by default.
    $this->connection->exec("SET NAMES 'UTF8'");

    // Execute PostgreSQL init_commands.
    if (isset($connection_options['init_commands'])) {
      $this->connection->exec(implode('; ', $connection_options['init_commands']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = array()) {
    // Default to TCP connection on port 5432.
    if (empty($connection_options['port'])) {
      $connection_options['port'] = 5432;
    }

    // PostgreSQL in trust mode doesn't require a password to be supplied.
    if (empty($connection_options['password'])) {
      $connection_options['password'] = NULL;
    }
    // If the password contains a backslash it is treated as an escape character
    // http://bugs.php.net/bug.php?id=53217
    // so backslashes in the password need to be doubled up.
    // The bug was reported against pdo_pgsql 1.0.2, backslashes in passwords
    // will break on this doubling up when the bug is fixed, so check the version
    //elseif (phpversion('pdo_pgsql') < 'version_this_was_fixed_in') {
    else {
      $connection_options['password'] = str_replace('\\', '\\\\', $connection_options['password']);
    }

    $connection_options['database'] = (!empty($connection_options['database']) ? $connection_options['database'] : 'template1');
    $dsn = 'pgsql:host=' . $connection_options['host'] . ' dbname=' . $connection_options['database'] . ' port=' . $connection_options['port'];

    // Allow PDO options to be overridden.
    $connection_options += array(
      'pdo' => array(),
    );
    $connection_options['pdo'] += array(
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      // Prepared statements are most effective for performance when queries
      // are recycled (used several times). However, if they are not re-used,
      // prepared statements become inefficient. Since most of Drupal's
      // prepared queries are not re-used, it should be faster to emulate
      // the preparation than to actually ready statements for re-use. If in
      // doubt, reset to FALSE and measure performance.
      \PDO::ATTR_EMULATE_PREPARES => TRUE,
      // Convert numeric values to strings when fetching.
      \PDO::ATTR_STRINGIFY_FETCHES => TRUE,
    );
    $pdo = new \PDO($dsn, $connection_options['username'], $connection_options['password'], $connection_options['pdo']);

    return $pdo;
  }

  /**
   * {@inheritdoc}
   */
  public function query($query, array $args = array(), $options = array()) {
    $options += $this->defaultOptions();

    // The PDO PostgreSQL driver has a bug which doesn't type cast booleans
    // correctly when parameters are bound using associative arrays.
    // @see http://bugs.php.net/bug.php?id=48383
    foreach ($args as &$value) {
      if (is_bool($value)) {
        $value = (int) $value;
      }
    }

    return parent::query($query, $args, $options);
  }

  public function prepareQuery($query) {
    // mapConditionOperator converts LIKE operations to ILIKE for consistency
    // with MySQL. However, Postgres does not support ILIKE on bytea (blobs)
    // fields.
    // To make the ILIKE operator work, we type-cast bytea fields into text.
    // @todo This workaround only affects bytea fields, but the involved field
    //   types involved in the query are unknown, so there is no way to
    //   conditionally execute this for affected queries only.
    return parent::prepareQuery(preg_replace('/ ([^ ]+) +(I*LIKE|NOT +I*LIKE) /i', ' ${1}::text ${2} ', $query));
  }

  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) {
    return $this->query($query . ' LIMIT ' . (int) $count . ' OFFSET ' . (int) $from, $args, $options);
  }

  public function queryTemporary($query, array $args = array(), array $options = array()) {
    $tablename = $this->generateTemporaryTableName();
    $this->query('CREATE TEMPORARY TABLE {' . $tablename . '} AS ' . $query, $args, $options);
    return $tablename;
  }

  /**
   * {@inheritdoc}
   */
  public function escapeField($field) {
    $escaped = parent::escapeField($field);

    // Remove any invalid start character.
    $escaped = preg_replace('/^[^A-Za-z0-9_]/', '', $escaped);

    // The pgsql database driver does not support field names that contain
    // periods (supported by PostgreSQL server) because this method may be
    // called by a field with a table alias as part of SQL conditions or
    // order by statements. This will consider a period as a table alias
    // identifier, and split the string at the first period.
    if (preg_match('/^([A-Za-z0-9_]+)"?[.]"?([A-Za-z0-9_.]+)/', $escaped, $parts)) {
      $table = $parts[1];
      $column = $parts[2];

      // Use escape alias because escapeField may contain multiple periods that
      // need to be escaped.
      $escaped = $this->escapeTable($table) . '.' . $this->escapeAlias($column);
    }
    elseif (preg_match('/[A-Z]/', $escaped)) {
      // Quote the field name for case-sensitivity.
      $escaped = '"' . $escaped . '"';
    }
    elseif (in_array(strtolower($escaped), $this->postgresqlReservedKeyWords)) {
      // Quote the field name for PostgreSQL reserved key words.
      $escaped = '"' . $escaped . '"';
    }

    return $escaped;
  }

  /**
   * {@inheritdoc}
   */
  public function escapeAlias($field) {
    $escaped = preg_replace('/[^A-Za-z0-9_]+/', '', $field);

    // Escape the alias in quotes for case-sensitivity.
    if (preg_match('/[A-Z]/', $escaped)) {
      $escaped = '"' . $escaped . '"';
    }
    elseif (in_array(strtolower($escaped), $this->postgresqlReservedKeyWords)) {
      // Quote the alias name for PostgreSQL reserved key words.
      $escaped = '"' . $escaped . '"';
    }

    return $escaped;
  }

  /**
   * {@inheritdoc}
   */
  public function escapeTable($table) {
    $escaped = parent::escapeTable($table);

    // Quote identifier to make it case-sensitive.
    if (preg_match('/[A-Z]/', $escaped)) {
      $escaped = '"' . $escaped . '"';
    }
    elseif (in_array(strtolower($escaped), $this->postgresqlReservedKeyWords)) {
      // Quote the table name for PostgreSQL reserved key words.
      $escaped = '"' . $escaped . '"';
    }

    return $escaped;
  }

  public function driver() {
    return 'pgsql';
  }

  public function databaseType() {
    return 'pgsql';
  }

  /**
   * Overrides \Drupal\Core\Database\Connection::createDatabase().
   *
   * @param string $database
   *   The name of the database to create.
   *
   * @throws \Drupal\Core\Database\DatabaseNotFoundException
   */
  public function createDatabase($database) {
    // Escape the database name.
    $database = Database::getConnection()->escapeDatabase($database);

    // If the PECL intl extension is installed, use it to determine the proper
    // locale.  Otherwise, fall back to en_US.
    if (class_exists('Locale')) {
      $locale = \Locale::getDefault();
    }
    else {
      $locale = 'en_US';
    }

    try {
      // Create the database and set it as active.
      $this->connection->exec("CREATE DATABASE $database WITH TEMPLATE template0 ENCODING='utf8' LC_CTYPE='$locale.utf8' LC_COLLATE='$locale.utf8'");
    }
    catch (\Exception $e) {
      throw new DatabaseNotFoundException($e->getMessage());
    }
  }

  public function mapConditionOperator($operator) {
    static $specials = array(
      // In PostgreSQL, 'LIKE' is case-sensitive. For case-insensitive LIKE
      // statements, we need to use ILIKE instead.
      'LIKE' => array('operator' => 'ILIKE'),
      'LIKE BINARY' => array('operator' => 'LIKE'),
      'NOT LIKE' => array('operator' => 'NOT ILIKE'),
      'REGEXP' => array('operator' => '~*'),
    );
    return isset($specials[$operator]) ? $specials[$operator] : NULL;
  }

  /**
   * Retrieve a the next id in a sequence.
   *
   * PostgreSQL has built in sequences. We'll use these instead of inserting
   * and updating a sequences table.
   */
  public function nextId($existing = 0) {

    // Retrieve the name of the sequence. This information cannot be cached
    // because the prefix may change, for example, like it does in simpletests.
    $sequence_name = $this->makeSequenceName('sequences', 'value');

    // When PostgreSQL gets a value too small then it will lock the table,
    // retry the INSERT and if it's still too small then alter the sequence.
    $id = $this->query("SELECT nextval('" . $sequence_name . "')")->fetchField();
    if ($id > $existing) {
      return $id;
    }

    // PostgreSQL advisory locks are simply locks to be used by an
    // application such as Drupal. This will prevent other Drupal processes
    // from altering the sequence while we are.
    $this->query("SELECT pg_advisory_lock(" . self::POSTGRESQL_NEXTID_LOCK . ")");

    // While waiting to obtain the lock, the sequence may have been altered
    // so lets try again to obtain an adequate value.
    $id = $this->query("SELECT nextval('" . $sequence_name . "')")->fetchField();
    if ($id > $existing) {
      $this->query("SELECT pg_advisory_unlock(" . self::POSTGRESQL_NEXTID_LOCK . ")");
      return $id;
    }

    // Reset the sequence to a higher value than the existing id.
    $this->query("ALTER SEQUENCE " . $sequence_name . " RESTART WITH " . ($existing + 1));

    // Retrieve the next id. We know this will be as high as we want it.
    $id = $this->query("SELECT nextval('" . $sequence_name . "')")->fetchField();

    $this->query("SELECT pg_advisory_unlock(" . self::POSTGRESQL_NEXTID_LOCK . ")");

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullQualifiedTableName($table) {
    $options = $this->getConnectionOptions();
    $prefix = $this->tablePrefix($table);

    // The fully qualified table name in PostgreSQL is in the form of
    // <database>.<schema>.<table>, so we have to include the 'public' schema in
    // the return value.
    return $options['database'] . '.public.' . $prefix . $table;
  }

  /**
   * Add a new savepoint with an unique name.
   *
   * The main use for this method is to mimic InnoDB functionality, which
   * provides an inherent savepoint before any query in a transaction.
   *
   * @param $savepoint_name
   *   A string representing the savepoint name. By default,
   *   "mimic_implicit_commit" is used.
   *
   * @see Drupal\Core\Database\Connection::pushTransaction().
   */
  public function addSavepoint($savepoint_name = 'mimic_implicit_commit') {
    if ($this->inTransaction()) {
      $this->pushTransaction($savepoint_name);
    }
  }

  /**
   * Release a savepoint by name.
   *
   * @param $savepoint_name
   *   A string representing the savepoint name. By default,
   *   "mimic_implicit_commit" is used.
   *
   * @see Drupal\Core\Database\Connection::popTransaction().
   */
  public function releaseSavepoint($savepoint_name = 'mimic_implicit_commit') {
    if (isset($this->transactionLayers[$savepoint_name])) {
      $this->popTransaction($savepoint_name);
    }
  }

  /**
   * Rollback a savepoint by name if it exists.
   *
   * @param $savepoint_name
   *   A string representing the savepoint name. By default,
   *   "mimic_implicit_commit" is used.
   */
  public function rollbackSavepoint($savepoint_name = 'mimic_implicit_commit') {
    if (isset($this->transactionLayers[$savepoint_name])) {
      $this->rollback($savepoint_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function upsert($table, array $options = array()) {
    // Use the (faster) native Upsert implementation for PostgreSQL >= 9.5.
    if (version_compare($this->version(), '9.5', '>=')) {
      $class = $this->getDriverClass('NativeUpsert');
    }
    else {
      $class = $this->getDriverClass('Upsert');
    }

    return new $class($this, $table, $options);
  }

}

/**
 * @} End of "addtogroup database".
 */
