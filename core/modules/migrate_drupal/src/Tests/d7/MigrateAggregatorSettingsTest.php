<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d7\MigrateAggregatorSettingsTest.
 */

namespace Drupal\migrate_drupal\Tests\d7;

/**
 * Tests migration of Aggregator's variables to configuration.
 *
 * @group migrate_drupal_7
 */
class MigrateAggregatorSettingsTest extends MigrateDrupal7TestBase {

  public static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d7_aggregator_settings');
  }

  /**
   * Tests migration of Aggregator variables to configuration.
   */
  public function testMigration() {
    $config = \Drupal::config('aggregator.settings')->get();
    $this->assertIdentical('aggregator', $config['fetcher']);
    $this->assertIdentical('aggregator', $config['parser']);
    $this->assertIdentical(['aggregator'], $config['processors']);
    $this->assertIdentical('<p> <div> <a>', $config['items']['allowed_html']);
    $this->assertIdentical(500, $config['items']['teaser_length']);
    $this->assertIdentical(86400, $config['items']['expire']);
    $this->assertIdentical(6, $config['source']['list_max']);
  }

}
