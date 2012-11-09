<?php

/**
 * @file
 * Definition of Drupal\rest\test\DBLogTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests the Watchdog resource to retrieve log messages.
 */
class DBLogTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rest', 'dblog');

  public static function getInfo() {
    return array(
      'name' => 'DB Log resource',
      'description' => 'Tests the watchdog database log resource.',
      'group' => 'REST',
    );
  }

  public function setUp() {
    parent::setUp();
    // Enable web API for the watchdog resource.
    $config = config('rest');
    $config->set('resources', array(
      'dblog' => 'dblog',
    ));
    $config->save();

    // Rebuild routing cache, so that the web API paths are available.
    drupal_container()->get('router.builder')->rebuild();
    // Reset the Simpletest permission cache, so that the new resource
    // permissions get picked up.
    drupal_static_reset('checkPermissions');
  }

  /**
   * Writes a log messages and retrieves it via the web API.
   */
  public function testWatchdog() {
    // Write a log message to the DB.
    watchdog('rest_test', 'Test message');
    // Get ID of the written message.
    $result = db_select('watchdog', 'w')
      ->condition('type', 'rest_test')
      ->fields('w', array('wid'))
      ->execute()
      ->fetchCol();
    $id = $result[0];

    // Create a user account that has the required permissions to read
    // the watchdog resource via the web API.
    $account = $this->drupalCreateUser(array('restful get dblog'));
    $this->drupalLogin($account);

    $response = $this->httpRequest("dblog/$id", 'GET');
    $this->assertResponse(200);
    $log = drupal_json_decode($response);
    $this->assertEqual($log['wid'], $id, 'Log ID is correct.');
    $this->assertEqual($log['type'], 'rest_test', 'Type of log message is correct.');
    $this->assertEqual($log['message'], 'Test message', 'Log message text is correct.');

    // Request an unknown log entry.
    $response = $this->httpRequest("dblog/9999", 'GET');
    $this->assertResponse(404);
    $this->assertEqual($response, 'Not Found', 'Response message is correct.');
  }
}
