<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\FilterTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views_test_data\Plugin\views\filter\FilterTest as FilterPlugin;

/**
 * Tests general filter plugin functionality.
 *
 * @see Drupal\views\Plugin\views\filter\FilterPluginBase
 */
class FilterTest extends PluginTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui');

  public static function getInfo() {
    return array(
      'name' => 'Filter: General',
      'description' => 'Tests general filter plugin functionality.',
      'group' => 'Views Plugins',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::viewsData().
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['filter']['id'] = 'test_filter';

    return $data;
  }

  /**
   * Test query of the row plugin.
   */
  public function testFilterQuery() {
    // Check that we can find the test filter plugin.
    $plugin = views_get_plugin('filter', 'test_filter');
    $this->assertTrue($plugin instanceof FilterPlugin, 'Test filter plugin found.');

    $view = views_get_view('test_filter');
    $view->initDisplay();

    // Change the filtering.
    $view->displayHandlers['default']->overrideOption('filters', array(
      'test_filter' => array(
        'id' => 'test_filter',
        'table' => 'views_test_data',
        'field' => 'name',
        'operator' => '=',
        'value' => 'John',
        'group' => 0,
      ),
    ));

    $this->executeView($view);

    // Make sure the query have where data.
    $this->assertTrue(!empty($view->query->where));

    // Check the data added.
    $where = $view->query->where;
    $this->assertIdentical($where[0]['conditions'][0]['field'], 'views_test_data.name', 'Where condition field matches');
    $this->assertIdentical($where[0]['conditions'][0]['value'], 'John', 'Where condition value matches');
    $this->assertIdentical($where[0]['conditions'][0]['operator'], '=', 'Where condition operator matches');

    $this->executeView($view);

    // Check that our operator and value match on the filter.
    $this->assertIdentical($view->filter['test_filter']->operator, '=');
    $this->assertIdentical($view->filter['test_filter']->value, 'John');

    // Check that we have some results.
    $this->assertEqual(count($view->result), 1, format_string('Results were returned. @count results.', array('@count' => count($view->result))));

    $view->destroy();

    $view->initDisplay();

    // Change the filtering.
    $view->displayHandlers['default']->overrideOption('filters', array(
      'test_filter' => array(
        'id' => 'test_filter',
        'table' => 'views_test_data',
        'field' => 'name',
        'operator' => '<>',
        'value' => 'John',
        'group' => 0,
      ),
    ));

    $this->executeView($view);

    // Check that our operator and value match on the filter.
    $this->assertIdentical($view->filter['test_filter']->operator, '<>');
    $this->assertIdentical($view->filter['test_filter']->value, 'John');

    // Test that no nodes have been returned (Only 'page' type nodes should
    // exist).
    $this->assertEqual(count($view->result), 4, format_string('No results were returned. @count results.', array('@count' => count($view->result))));

    $view->destroy();
    $view->initDisplay();

    // Set the test_enable option to FALSE. The 'where' clause should not be
    // added to the query.
    $view->displayHandlers['default']->overrideOption('filters', array(
      'test_filter' => array(
        'id' => 'test_filter',
        'table' => 'views_test_data',
        'field' => 'name',
        'operator' => '<>',
        'value' => 'John',
        'group' => 0,
        // Disable this option, so nothing should be added to the query.
        'test_enable' => FALSE,
      ),
    ));

    // Execute the view again.
    $this->executeView($view);

    // Check if we have all 5 results.
    $this->assertEqual(count($view->result), 5, format_string('All @count results returned', array('@count' => count($view->displayHandlers))));
  }

}
