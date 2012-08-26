<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\SortTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewsSchemaTestBase;

/**
 * Tests for core Drupal\views\Plugin\views\sort\SortPluginBase handler.
 */
class SortTest extends ViewsSchemaTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Sort: generic',
      'description' => 'Test the core Drupal\views\Plugin\views\sort\SortPluginBase handler.',
      'group' => 'Views Handlers',
    );
  }

  /**
   * Tests numeric ordering of the result set.
   */
  public function testNumericOrdering() {
    $view = $this->getBasicView();

    // Change the ordering
    $view->display['default']->handler->override_option('sorts', array(
      'age' => array(
        'order' => 'ASC',
        'id' => 'age',
        'table' => 'views_test',
        'field' => 'age',
        'relationship' => 'none',
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'age'), array(
      'views_test_name' => 'name',
      'views_test_age' => 'age',
    ));

    $view = $this->getBasicView();

    // Reverse the ordering
    $view->display['default']->handler->override_option('sorts', array(
      'age' => array(
        'order' => 'DESC',
        'id' => 'age',
        'table' => 'views_test',
        'field' => 'age',
        'relationship' => 'none',
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'age', TRUE), array(
      'views_test_name' => 'name',
      'views_test_age' => 'age',
    ));
  }

  /**
   * Tests string ordering of the result set.
   */
  public function testStringOrdering() {
    $view = $this->getBasicView();

    // Change the ordering
    $view->display['default']->handler->override_option('sorts', array(
      'name' => array(
        'order' => 'ASC',
        'id' => 'name',
        'table' => 'views_test',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'name'), array(
      'views_test_name' => 'name',
      'views_test_age' => 'age',
    ));

    $view = $this->getBasicView();

    // Reverse the ordering
    $view->display['default']->handler->override_option('sorts', array(
      'name' => array(
        'order' => 'DESC',
        'id' => 'name',
        'table' => 'views_test',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'name', TRUE), array(
      'views_test_name' => 'name',
      'views_test_age' => 'age',
    ));
  }
}
