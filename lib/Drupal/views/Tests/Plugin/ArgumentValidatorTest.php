<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\ArgumentValidatorTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\View;

/**
 * Tests Views argument validators.
 */
class ArgumentValidatorTest extends PluginTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Argument validator',
      'group' => 'Views Plugins',
      'description' => 'Test argument validator tests.',
    );
  }

  function testArgumentValidatePhp() {
    $string = $this->randomName();
    $view = $this->view_test_argument_validate_php($string);
    $view->setDisplay('default');
    $view->preExecute();
    $view->initHandlers();
    $this->assertTrue($view->argument['null']->validate_arg($string));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    $this->assertFalse($view->argument['null']->validate_arg($this->randomName()));
  }

  function testArgumentValidateNumeric() {
    $view = $this->view_argument_validate_numeric();
    $view->setDisplay('default');
    $view->preExecute();
    $view->initHandlers();
    $this->assertFalse($view->argument['null']->validate_arg($this->randomString()));
    // Reset safed argument validation.
    $view->argument['null']->argument_validated = NULL;
    $this->assertTrue($view->argument['null']->validate_arg(12));
  }

  function view_test_argument_validate_php($string) {
    $code = 'return $argument == \''. $string .'\';';
    $view = new View(array(), 'view');
    $view->name = 'view_argument_validate_numeric';
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
    $handler->display->display_options['arguments']['null']['validate']['type'] = 'php';
    $handler->display->display_options['arguments']['null']['validate_options']['code'] = $code;
    $handler->display->display_options['arguments']['null']['must_not_be'] = 0;

    return $view;
  }

  function view_argument_validate_numeric() {
    $view = new View(array(), 'view');
    $view->name = 'view_argument_validate_numeric';
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
    $handler->display->display_options['arguments']['null']['validate']['type'] = 'numeric';
    $handler->display->display_options['arguments']['null']['must_not_be'] = 0;

    return $view;
  }

}
