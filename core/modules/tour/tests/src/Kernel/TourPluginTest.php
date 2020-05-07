<?php

namespace Drupal\Tests\tour\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the functionality of tour plugins.
 *
 * @group tour
 */
class TourPluginTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['tour'];

  /**
   * Stores the tour plugin manager.
   *
   * @var \Drupal\tour\TipPluginManager
   */
  protected $pluginManager;

  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['tour']);
    $this->pluginManager = $this->container->get('plugin.manager.tour.tip');
  }

  /**
   * Test tour plugins.
   */
  public function testTourPlugins() {
    $this->assertCount(1, $this->pluginManager->getDefinitions(), 'Only tour plugins for the enabled modules were returned.');
  }

}
