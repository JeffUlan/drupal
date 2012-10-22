<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\SortTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests for core Drupal\views\Plugin\views\sort\SortPluginBase handler.
 */
class SortTest extends HandlerTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Sort: Generic',
      'description' => 'Test the core Drupal\views\Plugin\views\sort\SortPluginBase handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests numeric ordering of the result set.
   */
  public function testNumericOrdering() {
    $view = $this->getView();

    // Change the ordering
    $view->displayHandlers['default']->overrideOption('sorts', array(
      'age' => array(
        'order' => 'ASC',
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'age'), array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));

    $view = $this->getView();

    // Reverse the ordering
    $view->displayHandlers['default']->overrideOption('sorts', array(
      'age' => array(
        'order' => 'DESC',
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'age', TRUE), array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
  }

  /**
   * Tests string ordering of the result set.
   */
  public function testStringOrdering() {
    $view = $this->getView();

    // Change the ordering
    $view->displayHandlers['default']->overrideOption('sorts', array(
      'name' => array(
        'order' => 'ASC',
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'name'), array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));

    $view = $this->getView();

    // Reverse the ordering
    $view->displayHandlers['default']->overrideOption('sorts', array(
      'name' => array(
        'order' => 'DESC',
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ),
    ));

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), t('The number of returned rows match.'));
    $this->assertIdenticalResultset($view, $this->orderResultSet($this->dataSet(), 'name', TRUE), array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));
  }

}
