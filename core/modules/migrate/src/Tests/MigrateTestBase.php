<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\MigrateTestBase.
 */

namespace Drupal\migrate\Tests;

use Drupal\Core\Database\Database;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\simpletest\KernelTestBase;

/**
 * Creates abstract base class for migration tests.
 */
abstract class MigrateTestBase extends KernelTestBase implements MigrateMessageInterface {

  /**
   * TRUE to collect messages instead of displaying them.
   *
   * @var bool
   */
  protected $collectMessages = FALSE;

  /**
   * A two dimensional array of messages.
   *
   * The first key is the type of message, the second is just numeric. Values
   * are the messages.
   *
   * @var array
   */
  protected $migrateMessages;

  /**
   * The primary migration being tested.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * The source database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sourceDatabase;

  public static $modules = array('migrate');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createMigrationConnection();
    $this->sourceDatabase = Database::getConnection('default', 'migrate');
  }

  /**
   * Changes the database connection to the prefixed one.
   *
   * @todo Remove when we don't use global. https://www.drupal.org/node/2552791
   */
  private function createMigrationConnection() {
    // If the backup already exists, something went terribly wrong.
    // This case is possible, because database connection info is a static
    // global state construct on the Database class, which at least persists
    // for all test methods executed in one PHP process.
    if (Database::getConnectionInfo('simpletest_original_migrate')) {
      throw new \RuntimeException("Bad Database connection state: 'simpletest_original_migrate' connection key already exists. Broken test?");
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('migrate');
    if ($connection_info) {
      Database::renameConnection('migrate', 'simpletest_original_migrate');
    }
    $connection_info = Database::getConnectionInfo('default');
    foreach ($connection_info as $target => $value) {
      $prefix = is_array($value['prefix']) ? $value['prefix']['default'] : $value['prefix'];
      // Simpletest uses 7 character prefixes at most so this can't cause
      // collisions.
      $connection_info[$target]['prefix']['default'] = $prefix . '0';

      // Add the original simpletest prefix so SQLite can attach its database.
      // @see \Drupal\Core\Database\Driver\sqlite\Connection::init()
      $connection_info[$target]['prefix'][$value['prefix']['default']] = $value['prefix']['default'];
    }
    Database::addConnectionInfo('migrate', 'default', $connection_info['default']);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->cleanupMigrateConnection();
    parent::tearDown();
    $this->databaseDumpFiles = [];
    $this->collectMessages = FALSE;
    unset($this->migration, $this->migrateMessages);
  }

  /**
   * Cleans up the test migrate connection.
   *
   * @todo Remove when we don't use global. https://www.drupal.org/node/2552791
   */
  private function cleanupMigrateConnection() {
    Database::removeConnection('migrate');
    $original_connection_info = Database::getConnectionInfo('simpletest_original_migrate');
    if ($original_connection_info) {
      Database::renameConnection('simpletest_original_migrate', 'migrate');
    }
  }

  /**
   * Prepare any dependent migrations.
   *
   * @param array $id_mappings
   *   A list of ID mappings keyed by migration IDs. Each ID mapping is a list
   *   of two arrays, the first are source IDs and the second are destination
   *   IDs.
   */
  protected function prepareMigrations(array $id_mappings) {
    foreach ($id_mappings as $migration_id => $data) {
      // Use loadMultiple() here in order to load all variants.
      foreach (Migration::loadMultiple([$migration_id]) as $migration) {
        $id_map = $migration->getIdMap();
        $id_map->setMessage($this);
        $source_ids = $migration->getSourcePlugin()->getIds();
        foreach ($data as $id_mapping) {
          $row = new Row(array_combine(array_keys($source_ids), $id_mapping[0]), $source_ids);
          $id_map->saveIdMapping($row, $id_mapping[1]);
        }
      }
    }
  }

  /**
   * Executes a single migration.
   *
   * @param string|\Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration to execute, or its ID.
   */
  protected function executeMigration($migration) {
    if (is_string($migration)) {
      $this->migration = Migration::load($migration);
    }
    else {
      $this->migration = $migration;
    }
    if ($this instanceof MigrateDumpAlterInterface) {
      static::migrateDumpAlter($this);
    }
    (new MigrateExecutable($this->migration, $this))->import();
  }

  /**
   * Executes a set of migrations in dependency order.
   *
   * @param string[] $ids
   *   Array of migration IDs, in any order.
   */
  protected function executeMigrations(array $ids) {
    $migrations = Migration::loadMultiple($ids);
    array_walk($migrations, [$this, 'executeMigration']);
  }

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    if ($this->collectMessages) {
      $this->migrateMessages[$type][] = $message;
    }
    else {
      $this->assert($type == 'status', $message, 'migrate');
    }
  }

  /**
   * Start collecting messages and erase previous messages.
   */
  public function startCollectingMessages() {
    $this->collectMessages = TRUE;
    $this->migrateMessages = array();
  }

  /**
   * Stop collecting messages.
   */
  public function stopCollectingMessages() {
    $this->collectMessages = FALSE;
  }

  /**
   * Records a failure in the map table of a specific migration.
   *
   * This is done in order to test scenarios which require a failed row.
   *
   * @param string|\Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity, or its ID.
   * @param array $row
   *   The raw source row which "failed".
   * @param int $status
   *   (optional) The failure status. Should be one of the
   *   MigrateIdMapInterface::STATUS_* constants. Defaults to
   *   MigrateIdMapInterface::STATUS_FAILED.
   */
  protected function mockFailure($migration, array $row, $status = MigrateIdMapInterface::STATUS_FAILED) {
    if (is_string($migration)) {
      $migration = Migration::load($migration);
    }
    /** @var \Drupal\migrate\Entity\MigrationInterface $migration */
    $destination = array_map(function() { return NULL; }, $migration->getDestinationPlugin()->getIds());
    $row = new Row($row, $migration->getSourcePlugin()->getIds());
    $migration->getIdMap()->saveIdMapping($row, $destination, $status);
  }

}
