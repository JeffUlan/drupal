<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Looks up the value of a property based on a previous migration.
 *
 * It is important to maintain relationships among content coming from the
 * source site. For example, on the source site, a given user account may
 * have an ID of 123, but the Drupal user account created from it may have
 * a uid of 456. The migration process maintains the relationships between
 * source and destination identifiers in map tables, and this information
 * is leveraged by the migration_lookup process plugin.
 *
 * Available configuration keys
 * - migration: A single migration ID, or an array of migration IDs.
 * - source_ids: (optional) An array keyed by migration IDs with values that are
 *   a list of source properties.
 * - stub_id: (optional) Identifies the migration which will be used to create
 *   any stub entities.
 * - no_stub: (optional) Prevents the creation of a stub entity when no
 *   relationship is found in the migration map.
 *
 * Examples:
 *
 * Consider a node migration, where you want to maintain authorship. Let's
 * assume that users are previously migrated in a migration named 'users'. The
 * 'users' migration saved the mapping between the source and destination IDs in
 * a map table. The node migration example below maps the node 'uid' property so
 * that we first take the source 'author' value and then do a lookup for the
 * corresponding Drupal user ID from the map table.
 * @code
 * process:
 *   uid:
 *     plugin: migration_lookup
 *     migration: users
 *     source: author
 * @endcode
 *
 * The value of 'migration' can be a list of migration IDs. When using multiple
 * migrations it is possible each use different source identifiers. In this
 * case one can use source_ids which is an array keyed by the migration IDs
 * and the value is a list of source properties. See example below.
 * @code
 * process:
 *   uid:
 *     plugin: migration_lookup
 *       migration:
 *         - users
 *         - members
 *       source_ids:
 *         users:
 *           - author
 *         members:
 *           - id
 * @endcode
 *
 * If the migration_lookup plugin does not find the source ID in the migration
 * map it will create a stub entity for the relationship to use. This stub is
 * generated by the migration provided. In the case of multiple migrations the
 * first value of the migration list will be used, but you can select the
 * migration you wish to use by using the stub_id configuration key. The example
 * below uses 'members' migration to create stub entities.
 * @code
 * process:
 *   uid:
 *     plugin: migration_lookup
 *     migration:
 *       - users
 *       - members
 *     stub_id: members
 * @endcode
 *
 * To prevent the creation of a stub entity when no relationship is found in the
 * migration map, 'no_stub' configuration can be used as shown below.
 * @code
 * process:
 *   uid:
 *     plugin: migration_lookup
 *     migration: users
 *     no_stub: true
 *     source: author
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "migration_lookup"
 * )
 */
class MigrationLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManagerInterface $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->migration = $migration;
    $this->processPluginManager = $process_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $migration_ids = $this->configuration['migration'];
    if (!is_array($migration_ids)) {
      $migration_ids = [$migration_ids];
    }
    $self = FALSE;
    /** @var \Drupal\migrate\Plugin\MigrationInterface[] $migrations */
    $destination_ids = NULL;
    $source_id_values = [];
    $migrations = $this->migrationPluginManager->createInstances($migration_ids);
    foreach ($migrations as $migration_id => $migration) {
      if ($migration_id == $this->migration->id()) {
        $self = TRUE;
      }
      if (isset($this->configuration['source_ids'][$migration_id])) {
        $configuration = ['source' => $this->configuration['source_ids'][$migration_id]];
        $value = $this->processPluginManager
          ->createInstance('get', $configuration, $this->migration)
          ->transform(NULL, $migrate_executable, $row, $destination_property);
      }
      if (!is_array($value)) {
        $value = [$value];
      }
      $this->skipOnEmpty($value);
      $source_id_values[$migration_id] = $value;
      // Break out of the loop as soon as a destination ID is found.
      if ($destination_ids = $migration->getIdMap()->lookupDestinationId($source_id_values[$migration_id])) {
        break;
      }
    }

    if (!$destination_ids && !empty($this->configuration['no_stub'])) {
      return NULL;
    }

    if (!$destination_ids && ($self || isset($this->configuration['stub_id']) || count($migrations) == 1)) {
      // If the lookup didn't succeed, figure out which migration will do the
      // stubbing.
      if ($self) {
        $migration = $this->migration;
      }
      elseif (isset($this->configuration['stub_id'])) {
        $migration = $migrations[$this->configuration['stub_id']];
      }
      else {
        $migration = reset($migrations);
      }
      $destination_plugin = $migration->getDestinationPlugin(TRUE);
      // Only keep the process necessary to produce the destination ID.
      $process = $migration->getProcess();

      // We already have the source ID values but need to key them for the Row
      // constructor.
      $source_ids = $migration->getSourcePlugin()->getIds();
      $values = [];
      foreach (array_keys($source_ids) as $index => $source_id) {
        $values[$source_id] = $source_id_values[$migration->id()][$index];
      }

      $stub_row = $this->createStubRow($values + $migration->getSourceConfiguration(), $source_ids);

      // Do a normal migration with the stub row.
      $migrate_executable->processRow($stub_row, $process);
      $destination_ids = [];
      $id_map = $migration->getIdMap();
      try {
        $destination_ids = $destination_plugin->import($stub_row);
      }
      catch (\Exception $e) {
        $id_map->saveMessage($stub_row->getSourceIdValues(), $e->getMessage());
      }

      if ($destination_ids) {
        $id_map->saveIdMapping($stub_row, $destination_ids, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
      }
    }
    if ($destination_ids) {
      if (count($destination_ids) == 1) {
        return reset($destination_ids);
      }
      else {
        return $destination_ids;
      }
    }
  }

  /**
   * Skips the migration process entirely if the value is FALSE.
   *
   * @param mixed $value
   *   The incoming value to transform.
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  protected function skipOnEmpty(array $value) {
    if (!array_filter($value)) {
      throw new MigrateSkipProcessException();
    }
  }

  /**
   * Create a stub row source for later import as stub data.
   *
   * This simple wrapper of the Row constructor allows sub-classing plugins to
   * have more control over the row.
   *
   * @param array $values
   *   An array of values to add as properties on the object.
   * @param array $source_ids
   *   An array containing the IDs of the source using the keys as the field
   *   names.
   *
   * @return \Drupal\migrate\Row
   *   The stub row.
   */
  protected function createStubRow(array $values, array $source_ids) {
    return new Row($values, $source_ids, TRUE);
  }

}
