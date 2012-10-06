<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\StorageTestBase.
 */

namespace Drupal\system\Tests\KeyValueStore;

use Drupal\simpletest\UnitTestBase;

/**
 * Base class for testing key-value storages.
 */
abstract class StorageTestBase extends UnitTestBase {

  /**
   * The fully qualified class name of the key-value storage to test.
   *
   * @var string
   */
  protected $storageClass;

  /**
   * An array of random stdClass objects.
   *
   * @var array
   */
  protected $objects = array();

  protected function setUp() {
    parent::setUp();

    $this->collection1 = 'first';
    $this->collection2 = 'second';

    $this->store1 = new $this->storageClass($this->collection1);
    $this->store2 = new $this->storageClass($this->collection2);

    // Create several objects for testing.
    for ($i = 0; $i <= 5; $i++) {
      $this->objects[$i] = $this->randomObject();
    }
  }

  /**
   * Tests CRUD operations.
   */
  public function testCRUD() {
    // Verify that each store returns its own collection name.
    $this->assertIdentical($this->store1->getCollectionName(), $this->collection1);
    $this->assertIdentical($this->store2->getCollectionName(), $this->collection2);

    // Verify that an item can be stored.
    $this->store1->set('foo', $this->objects[0]);
    $this->assertIdenticalObject($this->objects[0], $this->store1->get('foo'));
    // Verify that the other collection is not affected.
    $this->assertFalse($this->store2->get('foo'));

    // Verify that an item can be updated.
    $this->store1->set('foo', $this->objects[1]);
    $this->assertIdenticalObject($this->objects[1], $this->store1->get('foo'));
    // Verify that the other collection is still not affected.
    $this->assertFalse($this->store2->get('foo'));

    // Verify that a collection/name pair is unique.
    $this->store2->set('foo', $this->objects[2]);
    $this->assertIdenticalObject($this->objects[1], $this->store1->get('foo'));
    $this->assertIdenticalObject($this->objects[2], $this->store2->get('foo'));

    // Verify that an item can be deleted.
    $this->store1->delete('foo');
    $this->assertFalse($this->store1->get('foo'));

    // Verify that the other collection is not affected.
    $this->assertIdenticalObject($this->objects[2], $this->store2->get('foo'));
    $this->store2->delete('foo');
    $this->assertFalse($this->store2->get('foo'));

    // Verify that multiple items can be stored.
    $values = array(
      'foo' => $this->objects[3],
      'bar' => $this->objects[4],
    );
    $this->store1->setMultiple($values);

    // Verify that multiple items can be retrieved.
    $result = $this->store1->getMultiple(array('foo', 'bar'));
    foreach ($values as $j => $value) {
      $this->assertIdenticalObject($value, $result[$j]);
    }

    // Verify that the other collection was not affected.
    $this->assertFalse($this->store2->get('foo'));
    $this->assertFalse($this->store2->get('bar'));

    // Verify that all items in a collection can be retrieved.
    // Ensure that an item with the same name exists in the other collection.
    $this->store2->set('foo', $this->objects[5]);
    $result = $this->store1->getAll();
    // Not using assertIdentical(), since the order is not defined for getAll().
    $this->assertEqual(count($result), count($values));
    foreach ($result as $key => $value) {
      $this->assertEqual($values[$key], $value);
    }
    // Verify that all items in the other collection are different.
    $result = $this->store2->getAll();
    $this->assertEqual($result, array('foo' => $this->objects[5]));

    // Verify that multiple items can be deleted.
    $this->store1->deleteMultiple(array_keys($values));
    $this->assertFalse($this->store1->get('foo'));
    $this->assertFalse($this->store1->get('bar'));
    $this->assertFalse($this->store1->getMultiple(array('foo', 'bar')));
    // Verify that the item in the other collection still exists.
    $this->assertIdenticalObject($this->objects[5], $this->store2->get('foo'));
  }

  /**
   * Tests expected behavior for non-existing keys.
   */
  public function testNonExistingKeys() {
    // Verify that a non-existing key returns NULL as value.
    $this->assertNull($this->store1->get('foo'));

    // Verify that a FALSE value can be stored.
    $this->store1->set('foo', FALSE);
    $this->assertIdentical($this->store1->get('foo'), FALSE);

    // Verify that a deleted key returns NULL as value.
    $this->store1->delete('foo');
    $this->assertNull($this->store1->get('foo'));

    // Verify that a non-existing key is not returned when getting multiple keys.
    $this->store1->set('bar', 'baz');
    $values = $this->store1->getMultiple(array('foo', 'bar'));
    $this->assertFalse(isset($values['foo']), "Key 'foo' not found.");
    $this->assertIdentical($values['bar'], 'baz');
  }

  /**
   * Tests the setIfNotExists() method.
   */
  public function testSetIfNotExists() {
    $key = $this->randomName();
    // Test that setIfNotExists() succeeds only the first time.
    for ($i = 0; $i <= 1; $i++) {
      // setIfNotExists() should be TRUE the first time (when $i is 0) and
      // FALSE the second time (when $i is 1).
      $this->assertEqual(!$i, $this->store1->setIfNotExists($key, $this->objects[$i]));
      $this->assertIdenticalObject($this->objects[0], $this->store1->get($key));
      // Verify that the other collection is not affected.
      $this->assertFalse($this->store2->get($key));
    }

    // Remove the item and try to set it again.
    $this->store1->delete($key);
    $this->store1->setIfNotExists($key, $this->objects[1]);
    // This time it should succeed.
    $this->assertIdenticalObject($this->objects[1], $this->store1->get($key));
    // Verify that the other collection is still not affected.
    $this->assertFalse($this->store2->get($key));
  }

}
