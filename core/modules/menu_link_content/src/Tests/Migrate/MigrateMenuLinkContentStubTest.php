<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Tests\Migrate\MigrateMenuLinkContentStubTest.
 */

namespace Drupal\menu_link_content\Tests\Migrate;

use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\migrate_drupal\Tests\StubTestTrait;

/**
 * Test stub creation for menu link content entities.
 *
 * @group menu_link_content
 */
class MigrateMenuLinkContentStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_link_content', 'link'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('menu_link_content');
  }

  /**
   * Tests creation of menu link content stubs.
   */
  public function testStub() {
    $this->performStubTest('menu_link_content');
  }

}
