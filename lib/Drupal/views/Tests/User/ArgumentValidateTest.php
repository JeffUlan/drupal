<?php

/**
 * @file
 * Definition of Drupal\views\Tests\User\ArgumentValidateTest.
 */

namespace Drupal\views\Tests\User;

use Drupal\views\View;

/**
 * Tests views user argument argument handler.
 */
class ArgumentValidateTest extends UserTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User: Argument validator',
      'description' => 'Tests user argument validator.',
      'group' => 'Views Plugins',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser();
  }

  function testArgumentValidateUserUid() {
    $account = $this->account;
    // test 'uid' case
    $view = $this->view_argument_validate_user('uid');
    $view->setDisplay('default');
    $view->preExecute();
    $view->initHandlers();
    $this->assertTrue($view->argument['null']->validateArgument($account->uid));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a string variable since type is 'uid'
    $this->assertFalse($view->argument['null']->validateArgument($account->name));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid numeric, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument(32));
  }

  function testArgumentValidateUserName() {
    $account = $this->account;
    // test 'name' case
    $view = $this->view_argument_validate_user('name');
    $view->setDisplay('default');
    $view->preExecute();
    $view->initHandlers();
    $this->assertTrue($view->argument['null']->validateArgument($account->name));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a uid variable since type is 'name'
    $this->assertFalse($view->argument['null']->validateArgument($account->uid));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid string, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument($this->randomName()));
  }

  function testArgumentValidateUserEither() {
    $account = $this->account;
    // test 'either' case
    $view = $this->view_argument_validate_user('either');
    $view->setDisplay('default');
    $view->preExecute();
    $view->initHandlers();
    $this->assertTrue($view->argument['null']->validateArgument($account->name));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a uid variable since type is 'name'
    $this->assertTrue($view->argument['null']->validateArgument($account->uid));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid string, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument($this->randomName()));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    // Fail for a valid uid, but for a user that doesn't exist
    $this->assertFalse($view->argument['null']->validateArgument(32));
  }

  function view_argument_validate_user($argtype) {
    $view = new View(array(), 'view');
    $view->name = 'view_argument_validate_user';
    $view->description = '';
    $view->tag = '';
    $view->view_php = '';
    $view->base_table = 'node';
    $view->is_cacheable = FALSE;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->newDisplay('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'none';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'fields';
    /* Argument: Global: Null */
    $handler->display->display_options['arguments']['null']['id'] = 'null';
    $handler->display->display_options['arguments']['null']['table'] = 'views';
    $handler->display->display_options['arguments']['null']['field'] = 'null';
    $handler->display->display_options['arguments']['null']['style_plugin'] = 'default_summary';
    $handler->display->display_options['arguments']['null']['default_argument_type'] = 'fixed';
    $handler->display->display_options['arguments']['null']['validate']['type'] = 'user';
    $handler->display->display_options['arguments']['null']['validate_options']['type'] = $argtype;
    $handler->display->display_options['arguments']['null']['must_not_be'] = 0;

    return $view;
  }

}
