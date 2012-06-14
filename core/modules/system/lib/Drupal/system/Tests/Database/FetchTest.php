<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\FetchTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\StatementInterface;
use PDO;

/**
 * Test fetch actions, part 1.
 *
 * We get timeout errors if we try to run too many tests at once.
 */
class FetchTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Fetch tests',
      'description' => 'Test the Database system\'s various fetch capabilities.',
      'group' => 'Database',
    );
  }

  /**
   * Confirm that we can fetch a record properly in default object mode.
   */
  function testQueryFetchDefault() {
    $records = array();
    $result = db_query('SELECT name FROM {test} WHERE age = :age', array(':age' => 25));
    $this->assertTrue($result instanceof StatementInterface, t('Result set is a Drupal statement object.'));
    foreach ($result as $record) {
      $records[] = $record;
      $this->assertTrue(is_object($record), t('Record is an object.'));
      $this->assertIdentical($record->name, 'John', t('25 year old is John.'));
    }

    $this->assertIdentical(count($records), 1, t('There is only one record.'));
  }

  /**
   * Confirm that we can fetch a record to an object explicitly.
   */
  function testQueryFetchObject() {
    $records = array();
    $result = db_query('SELECT name FROM {test} WHERE age = :age', array(':age' => 25), array('fetch' => PDO::FETCH_OBJ));
    foreach ($result as $record) {
      $records[] = $record;
      $this->assertTrue(is_object($record), t('Record is an object.'));
      $this->assertIdentical($record->name, 'John', t('25 year old is John.'));
    }

    $this->assertIdentical(count($records), 1, t('There is only one record.'));
  }

  /**
   * Confirm that we can fetch a record to an array associative explicitly.
   */
  function testQueryFetchArray() {
    $records = array();
    $result = db_query('SELECT name FROM {test} WHERE age = :age', array(':age' => 25), array('fetch' => PDO::FETCH_ASSOC));
    foreach ($result as $record) {
      $records[] = $record;
      if ($this->assertTrue(is_array($record), t('Record is an array.'))) {
        $this->assertIdentical($record['name'], 'John', t('Record can be accessed associatively.'));
      }
    }

    $this->assertIdentical(count($records), 1, t('There is only one record.'));
  }

  /**
   * Confirm that we can fetch a record into a new instance of a custom class.
   *
   * @see Drupal\system\Tests\Database\FakeRecord
   */
  function testQueryFetchClass() {
    $records = array();
    $result = db_query('SELECT name FROM {test} WHERE age = :age', array(':age' => 25), array('fetch' => 'Drupal\system\Tests\Database\FakeRecord'));
    foreach ($result as $record) {
      $records[] = $record;
      if ($this->assertTrue($record instanceof FakeRecord, t('Record is an object of class FakeRecord.'))) {
        $this->assertIdentical($record->name, 'John', t('25 year old is John.'));
      }
    }

    $this->assertIdentical(count($records), 1, t('There is only one record.'));
  }

  // Confirm that we can fetch a record into an indexed array explicitly.
  function testQueryFetchNum() {
    $records = array();
    $result = db_query('SELECT name FROM {test} WHERE age = :age', array(':age' => 25), array('fetch' => PDO::FETCH_NUM));
    foreach ($result as $record) {
      $records[] = $record;
      if ($this->assertTrue(is_array($record), t('Record is an array.'))) {
        $this->assertIdentical($record[0], 'John', t('Record can be accessed numerically.'));
      }
    }

    $this->assertIdentical(count($records), 1, 'There is only one record');
  }

  /**
   * Confirm that we can fetch a record into a doubly-keyed array explicitly.
   */
  function testQueryFetchBoth() {
    $records = array();
    $result = db_query('SELECT name FROM {test} WHERE age = :age', array(':age' => 25), array('fetch' => PDO::FETCH_BOTH));
    foreach ($result as $record) {
      $records[] = $record;
      if ($this->assertTrue(is_array($record), t('Record is an array.'))) {
        $this->assertIdentical($record[0], 'John', t('Record can be accessed numerically.'));
        $this->assertIdentical($record['name'], 'John', t('Record can be accessed associatively.'));
      }
    }

    $this->assertIdentical(count($records), 1, t('There is only one record.'));
  }

  /**
   * Confirm that we can fetch an entire column of a result set at once.
   */
  function testQueryFetchCol() {
    $records = array();
    $result = db_query('SELECT name FROM {test} WHERE age > :age', array(':age' => 25));
    $column = $result->fetchCol();
    $this->assertIdentical(count($column), 3, t('fetchCol() returns the right number of records.'));

    $result = db_query('SELECT name FROM {test} WHERE age > :age', array(':age' => 25));
    $i = 0;
    foreach ($result as $record) {
      $this->assertIdentical($record->name, $column[$i++], t('Column matches direct accesss.'));
    }
  }
}
