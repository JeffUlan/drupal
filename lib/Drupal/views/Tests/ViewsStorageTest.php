<?php

/**
 * Definition of Drupal\views\Tests\ViewsStorageTest.
 */

namespace Drupal\views\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\views\ViewStorageController;
use Drupal\views\View;
use Drupal\views\ViewsDisplay;

/**
 * Tests that functionality of the the ViewsStorageController.
 */
class ViewsStorageTest extends WebTestBase {

  /**
   * Properties that should be stored in the configuration.
   *
   * @var array
   */
  protected $config_properties = array(
    'disabled',
    'api_version',
    'name',
    'description',
    'tag',
    'base_table',
    'human_name',
    'core',
    'display',
  );

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  public static function getInfo() {
    return array(
      'name' => 'Views configurable CRUD tests',
      'description' => 'Test the CRUD functionality for ViewStorage.',
      'group' => 'Views',
    );
  }

  /**
   * Tests CRUD operations.
   */
  function testConfigurableCRUD() {

    // Get the Configurable information and controller.
    $info = entity_get_info('view');
    $controller = entity_get_controller('view');

    // Confirm that an info array has been returned.
    $this->assertTrue(!empty($info) && is_array($info), 'The View  info array is loaded.');

    // Confirm we have the correct controller class.
    $this->assertTrue($controller instanceof ViewStorageController, 'The correct controller is loaded.');

    // Load a single Configurable object from the controller.
    $load = $controller->load(array('archive'));
    $view = reset($load);

    // Confirm that an actual view object is loaded and that it returns all of
    // expected properties.
    $this->assertTrue($view instanceof View, 'Single View instance loaded.');
    foreach ($this->config_properties as $property) {
      $this->assertTrue(isset($view->{$property}), format_string('Property: @property loaded onto View.', array('@property' => $property)));
    }

    // Check the displays have been loaded correctly from config display data.
    $expected_displays = array('default', 'page', 'block');
    $this->assertEqual(array_keys($view->display), $expected_displays, 'The correct display names are present.');

    // Check each ViewDisplay object and confirm that it has the correct key.
    foreach ($view->display as $key => $display) {
      $this->assertTrue($display instanceof ViewsDisplay, format_string('Display: @display is instance of ViewsDisplay.', array('@display' => $key)));
      $this->assertEqual($key, $display->id, 'The display has the correct ID.');
      // Confirm that the display options array exists.
      $display_options = $display->display_options;
      $this->assertTrue(!empty($display_options) && is_array($display_options), 'Display options exist.');
    }

    // Fetch data for all Configurable objects and default view configurations.
    $all_configurables = $controller->load();
    $all_config = config_get_storage_names_with_prefix('views.view');

    // Remove the 'views.view.' prefix from config names for comparision with
    // loaded Configurable objects.
    $prefix_map = function ($value) {
      $parts = explode('.', $value);
      return end($parts);
    };

    // Check that the correct number of Configurable objects have been loaded.
    $count = count($all_configurables);
    $this->assertEqual($count, count($all_config), format_string('The array of all @count Configurable objects is loaded.', array('@count' => $count)));

    // Check that all of these machine names match.
    $this->assertIdentical(array_keys($all_configurables), array_map($prefix_map, $all_config), 'All loaded elements match.');

    // Create a new View instance with empty values.
    $created = $controller->create(array());

    $this->assertTrue($created instanceof View, 'Created object is a View.');
    // Check that the View contains all of the properties.
    foreach ($this->config_properties as $property) {
      $this->assertTrue(isset($view->{$property}), format_string('Property: @property created on View.', array('@property' => $property)));
    }

    // Create a new View instance with config values.
    $values = config('views.view.archive')->get();
    $created = $controller->create($values);

    $this->assertTrue($created instanceof View, 'Created object is a View.');
    // Check that the View contains all of the properties.
    $properties = $this->config_properties;
    array_pop($properties);

    // Test all properties except displays.
    foreach ($properties as $property) {
      $this->assertTrue(isset($created->{$property}), format_string('Property: @property created on View.', array('@property' => $property)));
      $this->assertIdentical($values[$property], $created->{$property}, format_string('Property value: @property matches configuration value.', array('@property' => $property)));
    }

    // Test created displays.
    foreach ($created->display as $key => $display) {
      $this->assertTrue($display instanceof ViewsDisplay, format_string('Display @display is an instance of ViewsDisplay.', array('@display' => $key)));
    }

    // Save the newly created view, but modify the name.
    // $created->set('name', 'archive_copy');
    // $created->save();

    // // Load the newly saved config.
    // $config = config('views.view.archive_copy');
    // $this->assertFalse($config->isNew(), 'Loaded configuration is not new.');

    // Change a value and save.
    $view->tag = 'changed';
    $view->save();
    debug($view->tag);

    // Check value have been written to config.
    $config = config('views.view.archive')->get();
    debug($config['tag']);
    $this->assertEqual($view->tag, $config['tag'], 'View property saved to config.');
  }

}
