<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Migrate\d7\MigrateNodeSettingsTest.
 */

namespace Drupal\node\Tests\Migrate\d7;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to node.settings config object.
 *
 * @group node
 */
class MigrateNodeSettingsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_node_settings');
  }

  /**
   * Tests migration of node variables to node.settings config object.
   */
  public function testAggregatorSettings() {
    $config = $this->config('node.settings');
    $this->assertEqual(1, $config->get('use_admin_theme'));
  }

}
