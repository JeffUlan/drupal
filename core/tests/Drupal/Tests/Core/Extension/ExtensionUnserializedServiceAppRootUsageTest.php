<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Extension\Extension;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Tests preferred use of service application root over DRUPAL_ROOT.
 *
 * @coversDefaultClass \Drupal\Core\Extension\Extension
 * @group Extension
 */
class ExtensionUnserializedServiceAppRootUsageTest extends UnitTestCase {

  /**
   * Tests that the Extension class unserialize method uses the preferred root.
   *
   * When the Extension unserialize method is called on serialized Extension
   * object data, test that the Extension object's root property is set to the
   * container's app.root and not the DRUPAL_ROOT constant if the service
   * container app.root is available.
   *
   * @covers ::unserialize
   */
  public function testServiceAppRouteUsage() {
    // The assumption of our test is that DRUPAL_ROOT is not defined.
    $this->assertFalse(defined('DRUPAL_ROOT'), 'Constant DRUPAL_ROOT is defined.');
    $container = new ContainerBuilder();
    // Set a dummy container app.root to test against.
    $container->set('app.root', '/dummy/app/root');
    \Drupal::setContainer($container);
    // Instantiate an Extension object for testing unserialization.
    $extension = new Extension($container->get('app.root'), 'module', 'core/modules/system/system.info.yml', 'system.module');
    $data = $extension->serialize();
    $extension->unserialize($data);
    $this->assertEquals('/dummy/app/root', $this->readAttribute($extension, 'root'));
  }

}
