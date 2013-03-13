<?php

/**
 * @file
 * Contains \Drupal\contact\Tests\Views\ContactFieldsTest.
 */

namespace Drupal\contact\Tests\Views;

use Drupal\views\Tests\ViewTestBase;

/**
 * Tests which checks that no fieldapi fields are added on contact.
 */
class ContactFieldsTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'text', 'contact');

  /**
   * Contains the field definition array attached to contact used for this test.
   *
   * @var array
   */
  protected $field;

  public static function getInfo() {
    return array(
      'name' => 'Contact: Field views data',
      'description' => 'Tests which checks that no fieldapi fields are added on contact.',
      'group' => 'Views module integration',
    );
  }

  protected function setUp() {
    parent::setUp();

    $field = array(
      'field_name' => strtolower($this->randomName()),
      'type' => 'text'
    );

    $this->field = field_create_field($field);

    $instance = array(
      'field_name' => $field['field_name'],
      'entity_type' => 'contact_message',
      'bundle' => 'contact_message',
    );
    field_create_instance($instance);
  }

  /**
   * Tests the views data generation.
   */
  public function testViewsData() {
    $field_name = $this->field['field_name'];
    $table_name = _field_sql_storage_tablename($this->field);
    $data = $this->container->get('views.views_data')->get($table_name);

    // Test that the expected data array is returned.
    $expected = array('', '_value', '_format');
    $this->assertEqual(count($data), count($expected), 'The expected amount of array keys were found.');
    foreach ($expected as $suffix) {
      $this->assertTrue(isset($data[$field_name . $suffix]));
    }
    $this->assertTrue(empty($data['table']['join']), 'The field is not joined to the non existent contact message base table.');
  }

}
