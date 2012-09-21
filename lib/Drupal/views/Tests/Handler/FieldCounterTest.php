<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FieldCounterTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests the Drupal\views\Plugin\views\field\Counter handler.
 */
class FieldCounterTest extends HandlerTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Field: Counter',
      'description' => 'Tests the Drupal\views\Plugin\views\field\Counter handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  function testSimple() {
    $view = $this->getView();
    $view->displayHandlers['default']->overrideOption('fields', array(
      'counter' => array(
        'id' => 'counter',
        'table' => 'views',
        'field' => 'counter',
        'relationship' => 'none',
      ),
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));
    $view->preview();

    $this->assertEqual(1, $view->style_plugin->rendered_fields[0]['counter']);
    $this->assertEqual(2, $view->style_plugin->rendered_fields[1]['counter']);
    $this->assertEqual(3, $view->style_plugin->rendered_fields[2]['counter']);
    $view->destroy();

    $view = $this->getView();
    $rand_start = rand(5, 10);
    $view->displayHandlers['default']->overrideOption('fields', array(
      'counter' => array(
        'id' => 'counter',
        'table' => 'views',
        'field' => 'counter',
        'relationship' => 'none',
        'counter_start' => $rand_start
      ),
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
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
