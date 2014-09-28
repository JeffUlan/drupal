<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldStorageCrudTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Field\FieldException;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests field storage create, read, update, and delete.
 *
 * @group field
 */
class FieldStorageCrudTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array();

  // TODO : test creation with
  // - a full fledged $field structure, check that all the values are there
  // - a minimal $field structure, check all default values are set
  // defer actual $field comparison to a helper function, used for the two cases above

  /**
   * Test the creation of a field storage.
   */
  function testCreate() {
    $field_storage_definition = array(
      'field_name' => 'field_2',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    field_test_memorize();
    $field_storage = entity_create('field_storage_config', $field_storage_definition);
    $field_storage->save();
    $mem = field_test_memorize();
    $this->assertIdentical($mem['field_test_field_storage_config_create'][0][0]->getName(), $field_storage_definition['field_name'], 'hook_entity_create() called with correct arguments.');
    $this->assertIdentical($mem['field_test_field_storage_config_create'][0][0]->getType(), $field_storage_definition['type'], 'hook_entity_create() called with correct arguments.');

    // Read the configuration. Check against raw configuration data rather than
    // the loaded ConfigEntity, to be sure we check that the defaults are
    // applied on write.
    $field_storage_config = \Drupal::config('field.storage.' . $field_storage->id())->get();

    // Ensure that basic properties are preserved.
    $this->assertEqual($field_storage_config['field_name'], $field_storage_definition['field_name'], 'The field name is properly saved.');
    $this->assertEqual($field_storage_config['entity_type'], $field_storage_definition['entity_type'], 'The field entity type is properly saved.');
    $this->assertEqual($field_storage_config['id'], $field_storage_definition['entity_type'] . '.' . $field_storage_definition['field_name'], 'The field id is properly saved.');
    $this->assertEqual($field_storage_config['type'], $field_storage_definition['type'], 'The field type is properly saved.');

    // Ensure that cardinality defaults to 1.
    $this->assertEqual($field_storage_config['cardinality'], 1, 'Cardinality defaults to 1.');

    // Ensure that default settings are present.
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $this->assertEqual($field_storage_config['settings'], $field_type_manager->getDefaultStorageSettings($field_storage_definition['type']), 'Default storage settings have been written.');

    // Guarantee that the name is unique.
    try {
      entity_create('field_storage_config', $field_storage_definition)->save();
      $this->fail(t('Cannot create two fields with the same name.'));
    }
    catch (EntityStorageException $e) {
      $this->pass(t('Cannot create two fields with the same name.'));
    }

    // Check that field type is required.
    try {
      $field_storage_definition = array(
        'field_name' => 'field_1',
        'entity_type' => 'entity_type',
      );
      entity_create('field_storage_config', $field_storage_definition)->save();
      $this->fail(t('Cannot create a field with no type.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with no type.'));
    }

    // Check that field name is required.
    try {
      $field_storage_definition = array(
        'type' => 'test_field',
        'entity_type' => 'entity_test',
      );
      entity_create('field_storage_config', $field_storage_definition)->save();
      $this->fail(t('Cannot create an unnamed field.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create an unnamed field.'));
    }
    // Check that entity type is required.
    try {
      $field_storage_definition = array(
        'field_name' => 'test_field',
        'type' => 'test_field'
      );
      entity_create('field_storage_config', $field_storage_definition)->save();
      $this->fail('Cannot create a field without an entity type.');
    }
    catch (FieldException $e) {
      $this->pass('Cannot create a field without an entity type.');
    }

    // Check that field name must start with a letter or _.
    try {
      $field_storage_definition = array(
        'field_name' => '2field_2',
        'entity_type' => 'entity_test',
        'type' => 'test_field',
      );
      entity_create('field_storage_config', $field_storage_definition)->save();
      $this->fail(t('Cannot create a field with a name starting with a digit.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with a name starting with a digit.'));
    }

    // Check that field name must only contain lowercase alphanumeric or _.
    try {
      $field_storage_definition = array(
        'field_name' => 'field#_3',
        'entity_type' => 'entity_test',
        'type' => 'test_field',
      );
      entity_create('field_storage_config', $field_storage_definition)->save();
      $this->fail(t('Cannot create a field with a name containing an illegal character.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with a name containing an illegal character.'));
    }

    // Check that field name cannot be longer than 32 characters long.
    try {
      $field_storage_definition = array(
        'field_name' => '_12345678901234567890123456789012',
        'entity_type' => 'entity_test',
        'type' => 'test_field',
      );
      entity_create('field_storage_config', $field_storage_definition)->save();
      $this->fail(t('Cannot create a field with a name longer than 32 characters.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field with a name longer than 32 characters.'));
    }

    // Check that field name can not be an entity key.
    // "id" is known as an entity key from the "entity_test" type.
    try {
      $field_storage_definition = array(
        'type' => 'test_field',
        'field_name' => 'id',
        'entity_type' => 'entity_test',
      );
      entity_create('field_storage_config', $field_storage_definition)->save();
      $this->fail(t('Cannot create a field bearing the name of an entity key.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot create a field bearing the name of an entity key.'));
    }
  }

  /**
   * Tests that an explicit schema can be provided on creation.
   *
   * This behavior is needed to allow field storage creation within updates,
   * since plugin classes (and thus the field type schema) cannot be accessed.
   */
  function testCreateWithExplicitSchema() {
    $schema = array(
      'dummy' => 'foobar'
    );
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_2',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'schema' => $schema,
    ));
    $this->assertEqual($field_storage->getSchema(), $schema);
  }

  /**
   * Tests reading field storage definitions.
   */
  function testRead() {
    $field_storage_definition = array(
      'field_name' => 'field_1',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    );
    $field_storage = entity_create('field_storage_config', $field_storage_definition);
    $field_storage->save();
    $id = $field_storage->id();

    // Check that 'single column' criteria works.
    $fields = entity_load_multiple_by_properties('field_storage_config', array('field_name' => $field_storage_definition['field_name']));
    $this->assertTrue(count($fields) == 1 && isset($fields[$id]), 'The field was properly read.');

    // Check that 'multi column' criteria works.
    $fields = entity_load_multiple_by_properties('field_storage_config', array('field_name' => $field_storage_definition['field_name'], 'type' => $field_storage_definition['type']));
    $this->assertTrue(count($fields) == 1 && isset($fields[$id]), 'The field was properly read.');
    $fields = entity_load_multiple_by_properties('field_storage_config', array('field_name' => $field_storage_definition['field_name'], 'type' => 'foo'));
    $this->assertTrue(empty($fields), 'No field was found.');

    // Create a field from the field storage.
    $field_definition = array(
      'field_name' => $field_storage_definition['field_name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
    entity_create('field_config', $field_definition)->save();
  }

  /**
   * Test creation of indexes on data column.
   */
  function testIndexes() {
    // Check that indexes specified by the field type are used by default.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_1',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ));
    $field_storage->save();
    $field_storage = entity_load('field_storage_config', $field_storage->id());
    $schema = $field_storage->getSchema();
    $expected_indexes = array('value' => array('value'));
    $this->assertEqual($schema['indexes'], $expected_indexes, 'Field type indexes saved by default');

    // Check that indexes specified by the field definition override the field
    // type indexes.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_2',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'indexes' => array(
        'value' => array(),
      ),
    ));
    $field_storage->save();
    $field_storage = entity_load('field_storage_config', $field_storage->id());
    $schema = $field_storage->getSchema();
    $expected_indexes = array('value' => array());
    $this->assertEqual($schema['indexes'], $expected_indexes, 'Field definition indexes override field type indexes');

    // Check that indexes specified by the field definition add to the field
    // type indexes.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_3',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'indexes' => array(
        'value_2' => array('value'),
      ),
    ));
    $field_storage->save();
    $id = $field_storage->id();
    $field_storage = entity_load('field_storage_config', $id);
    $schema = $field_storage->getSchema();
    $expected_indexes = array('value' => array('value'), 'value_2' => array('value'));
    $this->assertEqual($schema['indexes'], $expected_indexes, 'Field definition indexes are merged with field type indexes');
  }

  /**
   * Test the deletion of a field storage.
   */
  function testDelete() {
    // TODO: Also test deletion of the data stored in the field ?

    // Create two fields (so we can test that only one is deleted).
    $field_storage_definition = array(
      'field_name' => 'field_1',
      'type' => 'test_field',
      'entity_type' => 'entity_test',
    );
    entity_create('field_storage_config', $field_storage_definition)->save();
    $another_field_storage_definition = array(
      'field_name' => 'field_2',
      'type' => 'test_field',
      'entity_type' => 'entity_test',
    );
    entity_create('field_storage_config', $another_field_storage_definition)->save();

    // Create fields for each.
    $field_definition = array(
      'field_name' => $field_storage_definition['field_name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
    entity_create('field_config', $field_definition)->save();
    $another_field_definition = $field_definition;
    $another_field_definition['field_name'] = $another_field_storage_definition['field_name'];
    entity_create('field_config', $another_field_definition)->save();

    // Test that the first field is not deleted, and then delete it.
    $field_storage = current(entity_load_multiple_by_properties('field_storage_config', array('field_name' => $field_storage_definition['field_name'], 'include_deleted' => TRUE)));
    $this->assertTrue(!empty($field_storage) && empty($field_storage->deleted), 'A new storage is not marked for deletion.');
    FieldStorageConfig::loadByName('entity_test', $field_storage_definition['field_name'])->delete();

    // Make sure that the field is marked as deleted when it is specifically
    // loaded.
    $field_storage = current(entity_load_multiple_by_properties('field_storage_config', array('field_name' => $field_storage_definition['field_name'], 'include_deleted' => TRUE)));
    $this->assertTrue(!empty($field_storage->deleted), 'A deleted storage is marked for deletion.');

    // Make sure that this field is marked as deleted when it is
    // specifically loaded.
    $field = current(entity_load_multiple_by_properties('field_config', array('entity_type' => 'entity_test', 'field_name' => $field_definition['field_name'], 'bundle' => $field_definition['bundle'], 'include_deleted' => TRUE)));
    $this->assertTrue(!empty($field->deleted), 'A field whose storage was deleted is marked for deletion.');

    // Try to load the storage normally and make sure it does not show up.
    $field_storage = entity_load('field_storage_config', 'entity_test.' . $field_storage_definition['field_name']);
    $this->assertTrue(empty($field_storage), 'A deleted storage is not loaded by default.');

    // Try to load the field normally and make sure it does not show up.
    $field = entity_load('field_config', 'entity_test.' . '.' . $field_definition['bundle'] . '.' . $field_definition['field_name']);
    $this->assertTrue(empty($field), 'A field whose storage was deleted is not loaded by default.');

    // Make sure the other field and its storage are not deleted.
    $another_field_storage = entity_load('field_storage_config', 'entity_test.' . $another_field_storage_definition['field_name']);
    $this->assertTrue(!empty($another_field_storage) && empty($another_field_storage->deleted), 'A non-deleted storage is not marked for deletion.');
    $another_field = entity_load('field_config', 'entity_test.' . $another_field_definition['bundle'] . '.' . $another_field_definition['field_name']);
    $this->assertTrue(!empty($another_field) && empty($another_field->deleted), 'A field whose storage was not deleted is not marked for deletion.');

    // Try to create a new field the same name as a deleted field and
    // write data into it.
    entity_create('field_storage_config', $field_storage_definition)->save();
    entity_create('field_config', $field_definition)->save();
    $field_storage = entity_load('field_storage_config', 'entity_test.' . $field_storage_definition['field_name']);
    $this->assertTrue(!empty($field_storage) && empty($field_storage->deleted), 'A new storage with a previously used name is created.');
    $field = entity_load('field_config', 'entity_test.' . $field_definition['bundle'] . '.' . $field_definition['field_name'] );
    $this->assertTrue(!empty($field) && empty($field->deleted), 'A new field for a previously used field name is created.');

    // Save an entity with data for the field
    $entity = entity_create('entity_test');
    $values[0]['value'] = mt_rand(1, 127);
    $entity->{$field_storage->getName()}->value = $values[0]['value'];
    $entity = $this->entitySaveReload($entity);

    // Verify the field is present on load
    $this->assertIdentical(count($entity->{$field_storage->getName()}), count($values), "Data in previously deleted field saves and loads correctly");
    foreach ($values as $delta => $value) {
      $this->assertEqual($entity->{$field_storage->getName()}[$delta]->value, $values[$delta]['value'], "Data in previously deleted field saves and loads correctly");
    }
  }

  function testUpdateFieldType() {
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_type',
      'entity_type' => 'entity_test',
      'type' => 'decimal',
    ));
    $field_storage->save();

    try {
      $field_storage->type = 'integer';
      $field_storage->save();
      $this->fail(t('Cannot update a field to a different type.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot update a field to a different type.'));
    }
  }

  /**
   * Test updating a field storage.
   */
  function testUpdate() {
    // Create a field with a defined cardinality, so that we can ensure it's
    // respected. Since cardinality enforcement is consistent across database
    // systems, it makes a good test case.
    $cardinality = 4;
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_update',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => $cardinality,
    ));
    $field_storage->save();
    $field = entity_create('field_config', array(
      'field_storage' => $field_storage,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ));
    $field->save();

    do {
      $entity = entity_create('entity_test');
      // Fill in the entity with more values than $cardinality.
      for ($i = 0; $i < 20; $i++) {
        // We can not use $i here because 0 values are filtered out.
        $entity->field_update[$i]->value = $i + 1;
      }
      // Load back and assert there are $cardinality number of values.
      $entity = $this->entitySaveReload($entity);
      $this->assertEqual(count($entity->field_update), $field_storage->cardinality);
      // Now check the values themselves.
      for ($delta = 0; $delta < $cardinality; $delta++) {
        $this->assertEqual($entity->field_update[$delta]->value, $delta + 1);
      }
      // Increase $cardinality and set the field cardinality to the new value.
      $field_storage->cardinality = ++$cardinality;
      $field_storage->save();
    } while ($cardinality < 6);
  }

  /**
   * Test field type modules forbidding an update.
   */
  function testUpdateForbid() {
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'forbidden',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'settings' => array(
        'changeable' => 0,
        'unchangeable' => 0
    )));
    $field_storage->save();
    $field_storage->settings['changeable']++;
    try {
      $field_storage->save();
      $this->pass(t("A changeable setting can be updated."));
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->fail(t("An unchangeable setting cannot be updated."));
    }
    $field_storage->settings['unchangeable']++;
    try {
      $field_storage->save();
      $this->fail(t("An unchangeable setting can be updated."));
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass(t("An unchangeable setting cannot be updated."));
    }
  }

}
