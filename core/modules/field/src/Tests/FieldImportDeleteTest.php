<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldImportDeleteTest.
 */

namespace Drupal\field\Tests;

use Drupal\Component\Utility\String;

/**
 * Delete field and instances during config delete method invocation.
 *
 * @group field
 */
class FieldImportDeleteTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test_config');

  /**
   * Tests deleting fields and instances as part of config import.
   */
  public function testImportDelete() {
    // At this point there are 5 field configuration objects in the active
    // storage.
    // - field.field.entity_test.field_test_import
    // - field.field.entity_test.field_test_import_2
    // - field.instance.entity_test.entity_test.field_test_import
    // - field.instance.entity_test.entity_test.field_test_import_2
    // - field.instance.entity_test.test_bundle.field_test_import_2

    $field_name = 'field_test_import';
    $field_id = "entity_test.$field_name";
    $field_name_2 = 'field_test_import_2';
    $field_id_2 = "entity_test.$field_name_2";
    $instance_id = "entity_test.entity_test.$field_name";
    $instance_id_2a = "entity_test.entity_test.$field_name_2";
    $instance_id_2b = "entity_test.test_bundle.$field_name_2";
    $field_config_name = "field.field.$field_id";
    $field_config_name_2 = "field.field.$field_id_2";
    $instance_config_name = "field.instance.$instance_id";
    $instance_config_name_2a = "field.instance.$instance_id_2a";
    $instance_config_name_2b = "field.instance.$instance_id_2b";

    // Create a second bundle for the 'Entity test' entity type.
    entity_test_create_bundle('test_bundle');

    // Import default config.
    $this->installConfig(array('field_test_config'));

    // Get the uuid's for the fields.
    $field_uuid = entity_load('field_config', $field_id)->uuid();
    $field_uuid_2 = entity_load('field_config', $field_id_2)->uuid();

    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $this->copyConfig($active, $staging);
    $this->assertTrue($staging->delete($field_config_name), String::format('Deleted field: !field', array('!field' => $field_config_name)));
    $this->assertTrue($staging->delete($field_config_name_2), String::format('Deleted field: !field', array('!field' => $field_config_name_2)));
    $this->assertTrue($staging->delete($instance_config_name), String::format('Deleted field instance: !field_instance', array('!field_instance' => $instance_config_name)));
    $this->assertTrue($staging->delete($instance_config_name_2a), String::format('Deleted field instance: !field_instance', array('!field_instance' => $instance_config_name_2a)));
    $this->assertTrue($staging->delete($instance_config_name_2b), String::format('Deleted field instance: !field_instance', array('!field_instance' => $instance_config_name_2b)));

    $deletes = $this->configImporter()->getUnprocessedConfiguration('delete');
    $this->assertEqual(count($deletes), 5, 'Importing configuration will delete 3 field instances and 2 fields.');

    // Import the content of the staging directory.
    $this->configImporter()->import();

    // Check that the fields and instances are gone.
    $field = entity_load('field_config', $field_id, TRUE);
    $this->assertFalse($field, 'The field was deleted.');
    $field_2 = entity_load('field_config', $field_id_2, TRUE);
    $this->assertFalse($field_2, 'The second field was deleted.');
    $instance = entity_load('field_instance_config', $instance_id, TRUE);
    $this->assertFalse($instance, 'The field instance was deleted.');
    $instance_2a = entity_load('field_instance_config', $instance_id_2a, TRUE);
    $this->assertFalse($instance_2a, 'The second field instance on test bundle was deleted.');
    $instance_2b = entity_load('field_instance_config', $instance_id_2b, TRUE);
    $this->assertFalse($instance_2b, 'The second field instance on test bundle 2 was deleted.');

    // Check that all config files are gone.
    $active = $this->container->get('config.storage');
    $this->assertIdentical($active->listAll($field_config_name), array());
    $this->assertIdentical($active->listAll($field_config_name_2), array());
    $this->assertIdentical($active->listAll($instance_config_name), array());
    $this->assertIdentical($active->listAll($instance_config_name_2a), array());
    $this->assertIdentical($active->listAll($instance_config_name_2b), array());

    // Check that the field definition is preserved in state.
    $deleted_fields = \Drupal::state()->get('field.field.deleted') ?: array();
    $this->assertTrue(isset($deleted_fields[$field_uuid]));
    $this->assertTrue(isset($deleted_fields[$field_uuid_2]));

    // Purge field data, and check that the field definition has been completely
    // removed once the data is purged.
    field_purge_batch(10);
    $deleted_fields = \Drupal::state()->get('field.field.deleted') ?: array();
    $this->assertTrue(empty($deleted_fields), 'Fields are deleted');
  }
}

