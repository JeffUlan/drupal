<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\AccessTest
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\View;

/**
 * Basic test for pluggable access.
 */
class AccessTest extends PluginTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Access',
      'description' => 'Tests pluggable access for views.',
      'group' => 'Views Plugins'
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $this->admin_user = $this->drupalCreateUser(array('access all views'));
    $this->web_user = $this->drupalCreateUser();
    $this->web_role = current($this->web_user->roles);

    $this->normal_role = $this->drupalCreateRole(array());
    $this->normal_user = $this->drupalCreateUser(array('views_test test permission'));
    $this->normal_user->roles[$this->normal_role] = $this->normal_role;
    // Reset the plugin data.
    views_fetch_plugin_data(NULL, NULL, TRUE);
  }

  /**
   * Tests none access plugin.
   */
  function testAccessNone() {
    $view = $this->view_access_none();

    $view->setDisplay('default');

    $this->assertTrue($view->display_handler->access($this->admin_user), t('Admin-Account should be able to access the view everytime'));
    $this->assertTrue($view->display_handler->access($this->web_user));
    $this->assertTrue($view->display_handler->access($this->normal_user));
  }

  /**
   * Tests perm access plugin.
   */
  function testAccessPerm() {
    $view = $this->view_access_perm();

    $view->setDisplay('default');
    $access_plugin = $view->display_handler->getPlugin('access');

    $this->assertTrue($view->display_handler->access($this->admin_user), t('Admin-Account should be able to access the view everytime'));
    $this->assertFalse($view->display_handler->access($this->web_user));
    $this->assertTrue($view->display_handler->access($this->normal_user));
  }

  /**
   * Tests role access plugin.
   */
  function testAccessRole() {
    $view = $this->view_access_role();

    $view->setDisplay('default');
    $access_plugin = $view->display_handler->getPlugin('access');

    $this->assertTrue($view->display_handler->access($this->admin_user), t('Admin-Account should be able to access the view everytime'));
    $this->assertFalse($view->display_handler->access($this->web_user));
    $this->assertTrue($view->display_handler->access($this->normal_user));
  }

  /**
   * @todo Test abstract access plugin.
   */

  /**
   * Tests static access check.
   */
  function testStaticAccessPlugin() {
    $view = $this->view_access_static();

    $view->setDisplay('default');
    $access_plugin = $view->display_handler->getPlugin('access');

    $this->assertFalse($access_plugin->access($this->normal_user));

    $access_plugin->options['access'] = TRUE;
    $this->assertTrue($access_plugin->access($this->normal_user));

    // FALSE comes from hook_menu caching.
    $expected_hook_menu = array(
      'views_test_test_static_access_callback', array(FALSE)
    );
    $hook_menu = $view->executeHookMenu('page_1');
    $this->assertEqual($expected_hook_menu, $hook_menu['test_access_static']['access arguments'][0]);

    $expected_hook_menu = array(
      'views_test_test_static_access_callback', array(TRUE)
    );
    $this->assertTrue(views_access($expected_hook_menu));
  }

  /**
   * Tests dynamic access plugin.
   */
  function testDynamicAccessPlugin() {
    $view = $this->view_access_dynamic();
    $argument1 = $this->randomName();
    $argument2 = $this->randomName();
    variable_set('test_dynamic_access_argument1', $argument1);
    variable_set('test_dynamic_access_argument2', $argument2);

    $view->setDisplay('default');
    $access_plugin = $view->display_handler->getPlugin('access');

    $this->assertFalse($access_plugin->access($this->normal_user));

    $access_plugin->options['access'] = TRUE;
    $this->assertFalse($access_plugin->access($this->normal_user));

    $view->setArguments(array($argument1, $argument2));
    $this->assertTrue($access_plugin->access($this->normal_user));

    // FALSE comes from hook_menu caching.
    $expected_hook_menu = array(
      'views_test_test_dynamic_access_callback', array(FALSE, 1, 2)
    );
    $hook_menu = $view->executeHookMenu('page_1');
    $this->assertEqual($expected_hook_menu, $hook_menu['test_access_dynamic']['access arguments'][0]);

    $expected_hook_menu = array(
      'views_test_test_dynamic_access_callback', array(TRUE, 1, 2)
    );
    $this->assertTrue(views_access($expected_hook_menu, $argument1, $argument2));
  }

  function view_access_none() {
    $view = new View(array(), 'view');
    $view->name = 'test_access_none';
    $view->description = '';
    $view->tag = '';
    $view->view_php = '';
    $view->base_table = 'node';
    $view->is_cacheable = FALSE;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'none';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'fields';

    return $view;
  }

  function view_access_perm() {
    $view = new View(array(), 'view');
    $view->name = 'test_access_perm';
    $view->description = '';
    $view->tag = '';
    $view->view_php = '';
    $view->base_table = 'node';
    $view->is_cacheable = FALSE;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'perm';
    $handler->display->display_options['access']['perm'] = 'views_test test permission';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'fields';

    return $view;
  }

  function view_access_role() {
    $view = new View(array(), 'view');
    $view->name = 'test_access_role';
    $view->description = '';
    $view->tag = '';
    $view->view_php = '';
    $view->base_table = 'node';
    $view->is_cacheable = FALSE;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'role';
    $handler->display->display_options['access']['role'] = array(
      $this->normal_role => $this->normal_role,
    );
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'fields';

    return $view;
  }

  function view_access_dynamic() {
    $view = new View(array(), 'view');
    $view->name = 'test_access_dynamic';
    $view->description = '';
    $view->tag = '';
    $view->view_php = '';
    $view->base_table = 'node';
    $view->is_cacheable = FALSE;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'test_dynamic';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'fields';

    $handler = $view->new_display('page', 'Page', 'page_1');
    $handler->display->display_options['path'] = 'test_access_dynamic';

    return $view;
  }

  function view_access_static() {
    $view = new View(array(), 'view');
    $view->name = 'test_access_static';
    $view->description = '';
    $view->tag = '';
    $view->view_php = '';
    $view->base_table = 'node';
    $view->is_cacheable = FALSE;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'test_static';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'fields';

    $handler = $view->new_display('page', 'Page', 'page_1');
    $handler->display->display_options['path'] = 'test_access_static';

    return $view;
  }

}
