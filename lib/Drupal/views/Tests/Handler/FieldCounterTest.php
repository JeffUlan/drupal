<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FieldCounterTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewsSqlTest;

/**
 * Tests the views_handler_field_counter handler.
 */
class FieldCounterTest extends ViewsSqlTest {
  public static function getInfo() {
    return array(
      'name' => 'Field: Counter',
      'description' => 'Tests the views_handler_field_counter handler.',
      'group' => 'Views Handlers',
    );
  }

  function testSimple() {
    $view = $this->getBasicView();
    $view->display['default']->handler->override_option('fields', array(
      'counter' => array(
        'id' => 'counter',
        'table' => 'views',
        'field' => 'counter',
        'relationship' => 'none',
      ),
      'name' => array(
        'id' => 'name',
        'table' => 'views_test',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));
    $view->preview();

    $this->assertEqual(1, $view->style_plugin->rendered_fields[0]['counter']);
    $this->assertEqual(2, $view->style_plugin->rendered_fields[1]['counter']);
    $this->assertEqual(3, $view->style_plugin->rendered_fields[2]['counter']);
    $view->destroy();

    $view = $this->getBasicView();
    $rand_start = rand(5, 10);
    $view->display['default']->handler->override_option('fields', array(
      'counter' => array(
        'id' => 'counter',
        'table' => 'views',
        'field' => 'counter',
        'relationship' => 'none',
        'counter_start' => $rand_start
      ),
      'name' => array(
        'id' => 'name',
        'table' => 'views_test',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));
    $view->preview();

    $this->assertEqual(0 + $rand_start, $view->style_plugin->rendered_fields[0]['counter']);
    $this->assertEqual(1 + $rand_start, $view->style_plugin->rendered_fields[1]['counter']);
    $this->assertEqual(2 + $rand_start, $view->style_plugin->rendered_fields[2]['counter']);
  }

  // @TODO: Write tests for pager.
  function testPager() {
  }
}
