<?php

/**
 * @file
 * Definition of Drupal\user\Tests\TempStoreDatabaseTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\UnitTestBase;
use Drupal\user\TempStoreFactory;
use Drupal\Core\Lock\DatabaseLockBackend;
use Drupal\Core\Database\Database;

/**
 * Tests the TempStore namespace.
 *
 * @see Drupal\Core\TempStore\TempStore.
 */
class TempStoreDatabaseTest extends UnitTestBase {

  /**
   * A key/value store factory.
   *
   * @var Drupal\user\TempStoreFactory
   */
  protected $storeFactory;

  /**
   * The name of the key/value collection to set and retrieve.
   *
   * @var string
   */
  protected $collection;

  /**
   * An array of (fake) user IDs.
   *
   * @var array
   */
  protected $users = array();

  /**
   * An array of random stdClass objects.
   *
   * @var array
   */
  protected $objects = array();

  public static function getInfo() {
    return array(
      'name' => 'TempStore',
      'description' => 'Tests the temporary object storage system.',
      'group' => 'TempStore',
    );
  }

  protected function setUp() {
    parent::setUp();

    // Install system tables to test the key/value storage without installing a
    // full Drupal environment.
    module_load_install('system');
    $schema = system_schema();
    db_create_table('semaphore', $schema['semaphore']);
    db_create_table('key_value_expire', $schema['key_value_expire']);

    // Create a key/value collection.
    $this->storeFactory = new TempStoreFactory(Database::getConnection(), new DatabaseLockBackend());
    $this->collection = $this->randomName();

    // Create several objects for testing.
    for ($i = 0; $i <= 3; $i++) {
      $this->objects[$i] = $this->randomObject();
    }
    // Create two mock users for testing.
    for ($i = 0; $i <= 1; $i++) {
      $this->users[$i] = mt_rand(500, 5000000);
      $this->stores[$i] = $this->getStorePerUID($this->users[$i]);
    }

  }

  protected function tearDown() {
    db_drop_table('key_value_expire');
    db_drop_table('semaphore');
    parent::tearDown();
  }

  /**
   * Tests the UserTempStore API.
   */
  public function testUserTempStore() {
    $key = $this->randomName();
    // Test that setIfNotExists() succeeds only the first time.
    for ($i = 0; $i <= 1; $i++) {
      // setIfNotExists() should be TRUE the first time (when $i is 0) and
      // FALSE the second time (when $i is 1).
      $this->assertEqual(!$i, $this->stores[0]->setIfNotExists($key, $this->objects[$i]));
      $metadata = $this->stores[0]->getMetadata($key);
      $this->assertEqual($this->users[0], $metadata->owner);
      $this->assertIdenticalObject($this->objects[0], $this->stores[0]->get($key));
      // Another user should get the same result.
      $metadata = $this->stores[1]->getMetadata($key);
      $this->assertEqual($this->users[0], $metadata->owner);
      $this->assertIdenticalObject($this->objects[0], $this->stores[1]->get($key));
    }

    // Remove the item and try to set it again.
    $this->stores[0]->delete($key);
    $this->stores[0]->setIfNotExists($key, $this->objects[1]);
    // This time it should succeed.
    $this->assertIdenticalObject($this->objects[1], $this->stores[0]->get($key));

    // This user can update the object.
    $this->stores[0]->set($key, $this->objects[2]);
    $this->assertIdenticalObject($this->objects[2], $this->stores[0]->get($key));
    // The object is the same when another user loads it.
    $this->assertIdenticalObject($this->objects[2], $this->stores[1]->get($key));
    // Another user can update the object and become the owner.
    $this->stores[1]->set($key, $this->objects[3]);
    $this->assertIdenticalObject($this->objects[3], $this->stores[0]->get($key));
    $this->assertIdenticalObject($this->objects[3], $this->stores[1]->get($key));
    $metadata = $this->stores[1]->getMetadata($key);
    $this->assertEqual($this->users[1], $metadata->owner);

    // The first user should be informed that the second now owns the data.
    $metadata = $this->stores[0]->getMetadata($key);
    $this->assertEqual($this->users[1], $metadata->owner);

    // Now manually expire the item (this is not exposed by the API) and then
    // assert it is no longer accessible.
    db_update('key_value_expire')
      ->fields(array('expire' => REQUEST_TIME - 1))
      ->condition('collection', $this->collection)
      ->condition('name', $key)
      ->execute();
    $this->assertFalse($this->stores[0]->get($key));
    $this->assertFalse($this->stores[1]->get($key));
  }

  /**
   * Returns a TempStore for this collection belonging to the given user.
   *
   * @param int $uid
   *   A user ID.
   *
   * @return Drupal\user\TempStore
   *   The key/value store object.
   */
  protected function getStorePerUID($uid) {
    return $this->storeFactory->get($this->collection, $uid);
  }

}
