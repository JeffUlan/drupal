<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\CckMigration.
 */

namespace Drupal\migrate_drupal\Plugin\migrate;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration plugin class for migrations dealing with CCK field values.
 */
class CckMigration extends Migration implements ContainerFactoryPluginInterface {

  /**
   * Flag indicating whether the CCK data has been filled already.
   *
   * @var bool
   */
  protected $init = FALSE;

  /**
   * List of cckfield plugin IDs which have already run.
   *
   * @var string[]
   */
  protected $processedFieldTypes = [];

  /**
   * Already-instantiated cckfield plugins, keyed by ID.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface[]
   */
  protected $cckPluginCache;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a CckMigration.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $cck_manager
   *   The cckfield plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigratePluginManager $cck_manager, MigrationPluginManagerInterface $migration_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cckPluginManager = $cck_manager;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migrate.cckfield'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $source_plugin = $this->migrationPluginManager->createInstance($this->pluginId)->getSourcePlugin();
      if ($source_plugin instanceof RequirementsInterface) {
        try {
          $source_plugin->checkRequirements();
        }
        catch (RequirementsException $e) {
          // Kill the rest of the method.
          $source_plugin = [];
        }
      }
      foreach ($source_plugin as $row) {
        $field_type = $row->getSourceProperty('type');
        if (!isset($this->processedFieldTypes[$field_type]) && $this->cckPluginManager->hasDefinition($field_type)) {
          $this->processedFieldTypes[$field_type] = TRUE;
          // Allow the cckfield plugin to alter the migration as necessary so that
          // it knows how to handle fields of this type.
          if (!isset($this->cckPluginCache[$field_type])) {
            $this->cckPluginCache[$field_type] = $this->cckPluginManager->createInstance($field_type, [], $this);
          }
          call_user_func([$this->cckPluginCache[$field_type], $this->pluginDefinition['cck_plugin_method']], $this);
        }
      }
    }
    return parent::getProcess();
  }

}
