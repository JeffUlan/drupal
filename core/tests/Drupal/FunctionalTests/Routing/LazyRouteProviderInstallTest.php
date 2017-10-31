<?php

namespace Drupal\FunctionalTests\Routing;

use Drupal\Tests\BrowserTestBase;

/**
 * @group routing
 */
class LazyRouteProviderInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lazy_route_provider_install_test'];

  /**
   * Tests that the lazy route provider is used during a module install.
   */
  public function testInstallation() {
    $this->container->get('module_installer')->install(['router_test']);
    // Note that on DrupalCI the test site is installed in a sub directory so
    // we cannot use ::assertEquals().
    $this->assertStringEndsWith('/admin', \Drupal::state()->get('Drupal\lazy_route_provider_install_test\PluginManager'));
    $this->assertStringEndsWith('/router_test/test1', \Drupal::state()->get('router_test_install'));
  }

}
