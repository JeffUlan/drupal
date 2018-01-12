<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

/**
 * Provides a base class for testing migration upgrades in the UI.
 */
abstract class MigrateUpgradeTestBase extends BrowserTestBase {

  use MigrationConfigurationTrait;
  use CreateTestContentEntitiesTrait;

  /**
   * Use the Standard profile to test help implementations of many core modules.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * The source database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sourceDatabase;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createMigrationConnection();
    $this->sourceDatabase = Database::getConnection('default', 'migrate_drupal_ui');

    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);

    // Create content.
    $this->createContent();
  }

  /**
   * Loads a database fixture into the source database connection.
   *
   * @param string $path
   *   Path to the dump file.
   */
  protected function loadFixture($path) {
    $default_db = Database::getConnection()->getKey();
    Database::setActiveConnection($this->sourceDatabase->getKey());

    if (substr($path, -3) == '.gz') {
      $path = 'compress.zlib://' . $path;
    }
    require $path;

    Database::setActiveConnection($default_db);
  }

  /**
   * Changes the database connection to the prefixed one.
   *
   * @todo Remove when we don't use global. https://www.drupal.org/node/2552791
   */
  protected function createMigrationConnection() {
    $connection_info = Database::getConnectionInfo('default')['default'];
    if ($connection_info['driver'] === 'sqlite') {
      // Create database file in the test site's public file directory so that
      // \Drupal\simpletest\TestBase::restoreEnvironment() will delete this once
      // the test is complete.
      $file = $this->publicFilesDirectory . '/' . $this->testId . '-migrate.db.sqlite';
      touch($file);
      $connection_info['database'] = $file;
      $connection_info['prefix'] = '';
    }
    else {
      $prefix = is_array($connection_info['prefix']) ? $connection_info['prefix']['default'] : $connection_info['prefix'];
      // Simpletest uses fixed length prefixes. Create a new prefix for the
      // source database. Adding to the end of the prefix ensures that
      // \Drupal\simpletest\TestBase::restoreEnvironment() will remove the
      // additional tables.
      $connection_info['prefix'] = $prefix . '0';
    }

    Database::addConnectionInfo('migrate_drupal_ui', 'default', $connection_info);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    Database::removeConnection('migrate_drupal_ui');
    parent::tearDown();
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  public function testMigrateUpgrade() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $this->drupalGet('/upgrade');
    $session = $this->assertSession();
    $session->responseContains('Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal 8.');

    $this->drupalPostForm(NULL, [], t('Continue'));
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    $driver = $connection_options['driver'];
    $connection_options['prefix'] = $connection_options['prefix']['default'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    $edit = [
      $driver => $connection_options,
      'source_private_file_path' => $this->getSourceBasePath(),
      'version' => $version,
    ];
    if ($version == 6) {
      $edit['d6_source_base_path'] = $this->getSourceBasePath();
    }
    else {
      $edit['source_base_path'] = $this->getSourceBasePath();
    }
    if (count($drivers) !== 1) {
      $edit['driver'] = $driver;
    }
    $edits = $this->translatePostValues($edit);

    // Ensure submitting the form with invalid database credentials gives us a
    // nice warning.
    $this->drupalPostForm(NULL, [$driver . '[database]' => 'wrong'] + $edits, t('Review upgrade'));
    $session->pageTextContains('Resolve the issue below to continue the upgrade.');

    $this->drupalPostForm(NULL, $edits, t('Review upgrade'));
    // Ensure we get errors about missing modules.
    $session->pageTextContains(t('Resolve the issue below to continue the upgrade'));
    $session->pageTextContains(t('The no_source_module plugin must define the source_module property.'));

    // Uninstall the module causing the missing module error messages.
    $this->container->get('module_installer')->uninstall(['migration_provider_test'], TRUE);

    // Restart the upgrade process.
    $this->drupalGet('/upgrade');
    $session->responseContains('Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal 8.');

    $this->drupalPostForm(NULL, [], t('Continue'));
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    $this->drupalPostForm(NULL, $edits, t('Review upgrade'));
    $session->pageTextContains('WARNING: Content may be overwritten on your new site.');
    $session->pageTextContains('There is conflicting content of these types:');
    $session->pageTextContains('aggregator feed entities');
    $session->pageTextContains('aggregator feed item entities');
    $session->pageTextContains('custom block entities');
    $session->pageTextContains('custom menu link entities');
    $session->pageTextContains('file entities');
    $session->pageTextContains('taxonomy term entities');
    $session->pageTextContains('user entities');
    $session->pageTextContains('comments');
    $session->pageTextContains('content item revisions');
    $session->pageTextContains('content items');
    $session->pageTextContains('There is translated content of these types:');
    $this->drupalPostForm(NULL, [], t('I acknowledge I may lose data. Continue anyway.'));
    $session->statusCodeEquals(200);
    $session->pageTextContains('Upgrade analysis report');
    // Ensure there are no errors about the missing modules from the test module.
    $session->pageTextNotContains(t('Source module not found for migration_provider_no_annotation.'));
    $session->pageTextNotContains(t('Source module not found for migration_provider_test.'));
    // Ensure there are no errors about any other missing migration providers.
    $session->pageTextNotContains(t('module not found'));

    // Test the available migration paths.
    $all_available = $this->getAvailablePaths();
    foreach ($all_available as $available) {
      $session->elementExists('xpath', "//span[contains(@class, 'checked') and text() = '$available']");
      $session->elementNotExists('xpath', "//span[contains(@class, 'warning') and text() = '$available']");
    }

    // Test the missing migration paths.
    $all_missing = $this->getMissingPaths();
    foreach ($all_missing as $missing) {
      $session->elementExists('xpath', "//span[contains(@class, 'warning') and text() = '$missing']");
      $session->elementNotExists('xpath', "//span[contains(@class, 'checked') and text() = '$missing']");
    }

    $this->drupalPostForm(NULL, [], t('Perform upgrade'));
    $this->assertText(t('Congratulations, you upgraded Drupal!'));

    // Have to reset all the statics after migration to ensure entities are
    // loadable.
    $this->resetAll();

    $expected_counts = $this->getEntityCounts();
    foreach (array_keys(\Drupal::entityTypeManager()
      ->getDefinitions()) as $entity_type) {
      $real_count = \Drupal::entityQuery($entity_type)->count()->execute();
      $expected_count = isset($expected_counts[$entity_type]) ? $expected_counts[$entity_type] : 0;
      $this->assertEqual($expected_count, $real_count, "Found $real_count $entity_type entities, expected $expected_count.");
    }

    $plugin_manager = \Drupal::service('plugin.manager.migration');
    /** @var \Drupal\migrate\Plugin\Migration[] $all_migrations */
    $all_migrations = $plugin_manager->createInstancesByTag('Drupal ' . $version);
    foreach ($all_migrations as $migration) {
      $id_map = $migration->getIdMap();
      foreach ($id_map as $source_id => $map) {
        // Convert $source_id into a keyless array so that
        // \Drupal\migrate\Plugin\migrate\id_map\Sql::getSourceHash() works as
        // expected.
        $source_id_values = array_values(unserialize($source_id));
        $row = $id_map->getRowBySource($source_id_values);
        $destination = serialize($id_map->currentDestination());
        $message = "Migration of $source_id to $destination as part of the {$migration->id()} migration. The source row status is " . $row['source_row_status'];
        // A completed migration should have maps with
        // MigrateIdMapInterface::STATUS_IGNORED or
        // MigrateIdMapInterface::STATUS_IMPORTED.
        if ($row['source_row_status'] == MigrateIdMapInterface::STATUS_FAILED || $row['source_row_status'] == MigrateIdMapInterface::STATUS_NEEDS_UPDATE) {
          $this->fail($message);
        }
        else {
          $this->pass($message);
        }
      }
    }
    \Drupal::service('module_installer')->install(['forum']);
    \Drupal::service('module_installer')->install(['book']);
  }

  /**
   * Transforms a nested array into a flat array suitable for BrowserTestBase::drupalPostForm().
   *
   * @param array $values
   *   A multi-dimensional form values array to convert.
   *
   * @return array
   *   The flattened $edit array suitable for BrowserTestBase::drupalPostForm().
   */
  protected function translatePostValues(array $values) {
    $edit = [];
    // The easiest and most straightforward way to translate values suitable for
    // BrowserTestBase::drupalPostForm() is to actually build the POST data string
    // and convert the resulting key/value pairs back into a flat array.
    $query = http_build_query($values);
    foreach (explode('&', $query) as $item) {
      list($key, $value) = explode('=', $item);
      $edit[urldecode($key)] = urldecode($value);
    }
    return $edit;
  }

  /**
   * Gets the source base path for the concrete test.
   *
   * @return string
   *   The source base path.
   */
  abstract protected function getSourceBasePath();

  /**
   * Gets the expected number of entities per entity type after migration.
   *
   * @return int[]
   *   An array of expected counts keyed by entity type ID.
   */
  abstract protected function getEntityCounts();

  /**
   * Gets the available upgrade paths.
   *
   * @return string[]
   *   An array of available upgrade paths.
   */
  abstract protected function getAvailablePaths();

  /**
   * Gets the missing upgrade paths.
   *
   * @return string[]
   *   An array of missing upgrade paths.
   */
  abstract protected function getMissingPaths();

}
