<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\ThemeExtensionList
 * @group Extension
 */
class ThemeExtensionListTest extends KernelTestBase {

  /**
   * @covers ::getList
   */
  public function testGetlist() {
    \Drupal::configFactory()->getEditable('core.extension')
      ->set('module.testing', 1000)
      ->set('theme.test_theme', 0)
      ->save();

    // The installation profile is provided by a container parameter.
    // Saving the configuration doesn't automatically trigger invalidation
    $this->container->get('kernel')->rebuildContainer();

    /** @var \Drupal\Core\Extension\ThemeExtensionList $theme_extension_list */
    $theme_extension_list = \Drupal::service('extension.list.theme');
    $extensions = $theme_extension_list->getList();

    $this->assertArrayHasKey('test_theme', $extensions);
  }

}
