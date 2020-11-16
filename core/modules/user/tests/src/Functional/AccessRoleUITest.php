<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\views_ui\Functional\UITestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests views role access plugin UI.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\access\Role
 */
class AccessRoleUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_access_role'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user', 'user_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(static::class, ['user_test_views']);
  }

  /**
   * Tests the role access plugin UI.
   */
  public function testAccessRoleUI() {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('user_role')->create(['id' => 'custom_role', 'label' => 'Custom role'])->save();
    $access_url = "admin/structure/views/nojs/display/test_access_role/default/access_options";
    $this->drupalPostForm($access_url, ['access_options[role][custom_role]' => 1], 'Apply');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [], 'Save');
    $view = $entity_type_manager->getStorage('view')->load('test_access_role');

    $display = $view->getDisplay('default');
    $this->assertEqual($display['display_options']['access']['options']['role'], ['custom_role' => 'custom_role']);

    // Test changing access plugin from role to none.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_access_role/default/access', ['access[type]' => 'none'], 'Apply');
    $this->drupalPostForm(NULL, [], 'Save');
    // Verify that role option is not set.
    $view = $entity_type_manager->getStorage('view')->load('test_access_role');
    $display = $view->getDisplay('default');
    $this->assertFalse(isset($display['display_options']['access']['options']['role']));
  }

}
