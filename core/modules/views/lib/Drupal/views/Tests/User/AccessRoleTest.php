<?php

/**
 * @file
 * Definition of Drupal\views\Tests\User\AccessRoleTest.
 */

namespace Drupal\views\Tests\User;

use Drupal\user\Plugin\views\access\Role;

/**
 * Tests views role access plugin.
 *
 * @see Drupal\user\Plugin\views\access\Role
 */
class AccessRoleTest extends AccessTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User: Access role',
      'description' => 'Tests views role access plugin.',
      'group' => 'Views Modules',
    );
  }

  /**
   * Tests role access plugin.
   */
  function testAccessRole() {
    $view = $this->createViewFromConfig('test_access_role');

    $view->displayHandlers['default']->options['access']['options']['role'] = array(
      $this->normalRole => $this->normalRole,
    );

    $access_plugin = $view->display_handler->getPlugin('access');
    $this->assertTrue($access_plugin instanceof Role, 'Make sure the right class got instantiated.');


    $this->assertTrue($view->display_handler->access($this->adminUser), t('Admin-Account should be able to access the view everytime'));
    $this->assertFalse($view->display_handler->access($this->webUser));
    $this->assertTrue($view->display_handler->access($this->normalUser));
  }

}
