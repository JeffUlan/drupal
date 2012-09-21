<?php

/**
 * @file
 * Definition of Drupal\views\Tests\ViewTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\ViewExecutable;
use Drupal\views\ViewStorage;

/**
 * Views class tests.
 */
class ViewTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment');

  public static function getInfo() {
    return array(
      'name' => 'Views object',
      'description' => 'Tests some functionality of the View class.',
      'group' => 'Views',
    );
  }

  /**
   * Tests the deconstructor to be sure that every kind of heavy objects are removed.
   */
  function testDestroy() {
    $view = $this->getView();

    $view->preview();
    $view->destroy();

    $this->assertViewDestroy($view);

    // Manipulate the display variable to test a previous bug.
    $view = $this->getView();
    $view->preview();

    $view->destroy();
    $this->assertViewDestroy($view);
  }

  function assertViewDestroy($view) {
    $this->assertFalse(isset($view->displayHandlers['default']), 'Make sure all displays are destroyed.');
    $this->assertFalse(isset($view->displayHandlers['attachment_1']), 'Make sure all displays are destroyed.');

    $this->assertFalse(isset($view->filter), 'Make sure all handlers are destroyed');
    $this->assertFalse(isset($view->field), 'Make sure all handlers are destroyed');
    $this->assertFalse(isset($view->argument), 'Make sure all handlers are destroyed');
    $this->assertFalse(isset($view->relationship), 'Make sure all handlers are destroyed');
    $this->assertFalse(isset($view->sort), 'Make sure all handlers are destroyed');
    $this->assertFalse(isset($view->area), 'Make sure all handlers are destroyed');

    $keys = array('current_display', 'display_handler', 'field', 'argument', 'filter', 'sort', 'relationship', 'header', 'footer', 'empty', 'query', 'result', 'inited', 'style_plugin', 'plugin_name', 'exposed_data', 'exposed_input', 'many_to_one_tables');
    foreach ($keys as $key) {
      $this->assertFalse(isset($view->{$key}), $key);
    }
    $this->assertEqual($view->built, FALSE);
    $this->assertEqual($view->executed, FALSE);
    $this->assertEqual($view->build_info, array());
    $this->assertEqual($view->attachment_before, '');
    $this->assertEqual($view->attachment_after, '');
  }

  /**
   * Tests ViewExecutable::viewsHandlerTypes().
   */
  public function testViewshandlerTypes() {
    $types = ViewExecutable::viewsHandlerTypes();
    foreach (array('field', 'filter', 'argument', 'sort', 'header', 'footer', 'empty') as $type) {
      $this->assertTrue(isset($types[$type]));
      // @todo The key on the display should be footers, headers and empties
      // or something similar instead of the singular, but so long check for
      // this special case.
      if (isset($types[$type]['type']) && $types[$type]['type'] == 'area') {
        $this->assertEqual($types[$type]['plural'], $type);
      }
      else {
        $this->assertEqual($types[$type]['plural'], $type . 's');
      }
    }
  }

  function testValidate() {
    // Test a view with multiple displays.
    // Validating a view shouldn't change the active display.
    // @todo: Create an extra validation view.
    $this->view->setDisplay('page_1');

    $this->view->validate();

    $this->assertEqual('page_1', $this->view->current_display, "The display should be constant while validating");

    // @todo: Write real tests for the validation.
    // In general the following things could be tested:
    // - Deleted displays shouldn't be validated
    // - Multiple displays are validating and the errors are merged together.
  }

  /**
   * Tests the createDuplicate() View method.
   */
  public function testCreateDuplicate() {
    $view = views_get_view('archive');
    $copy = $view->createDuplicate();

    $this->assertTrue($copy instanceof ViewStorage, 'The copied object is a View.');

    // Check that the original view and the copy have different uuids.
    $this->assertNotIdentical($view->uuid(), $copy->uuid(), 'The copied view has a new uuid.');

    // Check the 'name' (id) is using the View objects default value ('') as it
    // gets unset.
    $this->assertIdentical($copy->id(), '', 'The ID has been reset.');

    // Check the other properties.
    // @todo Create a reusable property on the base test class for these?
    $config_properties = array(
      'disabled',
      'api_version',
      'description',
      'tag',
      'base_table',
      'human_name',
      'core',
    );

    foreach ($config_properties as $property) {
      $this->assertIdentical($view->{$property}, $copy->{$property}, format_string('@property property is identical.', array('@property' => $property)));
    }

    // Check the displays are the same.
    foreach ($view->display as $id => $display) {
      // assertIdentical will not work here.
      $this->assertEqual($display, $copy->display[$id], format_string('The @display display has been copied correctly.', array('@display' => $id)));
    }
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::getBasicView().
   */
  protected function getBasicView() {
    return $this->createViewFromConfig('test_destroy');
  }

}
