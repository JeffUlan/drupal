<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FieldFileSizeTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests the core Drupal\views\Plugin\views\field\FileSize handler.
 *
 * @see CommonXssUnitTest
 */
class FieldFileSizeTest extends HandlerTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Field: File size',
      'description' => 'Test the core Drupal\views\Plugin\views\field\FileSize handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  function dataSet() {
    $data = parent::dataSet();
    $data[0]['age'] = 0;
    $data[1]['age'] = 10;
    $data[2]['age'] = 1000;
    $data[3]['age'] = 10000;

    return $data;
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['age']['field']['id'] = 'file_size';

    return $data;
  }

  public function testFieldFileSize() {
    $view = $this->getView();

    $view->displayHandlers['default']->overrideOption('fields', array(
      'age' => array(
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
      ),
    ));

    $this->executeView($view);

    // Test with the formatted option.
    $this->assertEqual($view->field['age']->advanced_render($view->result[0]), '');
    $this->assertEqual($view->field['age']->advanced_render($view->result[1]), '10 bytes');
    $this->assertEqual($view->field['age']->advanced_render($view->result[2]), '1000 bytes');
    $this->assertEqual($view->field['age']->advanced_render($view->result[3]), '9.77 KB');
    // Test with the bytes option.
    $view->field['age']->options['file_size_display'] = 'bytes';
    $this->assertEqual($view->field['age']->advanced_render($view->result[0]), '');
    $this->assertEqual($view->field['age']->advanced_render($view->result[1]), 10);
    $this->assertEqual($view->field['age']->advanced_render($view->result[2]), 1000);
    $this->assertEqual($view->field['age']->advanced_render($view->result[3]), 10000);
  }

}
