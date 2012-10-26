<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\CronRunTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

class CronRunTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('common_test', 'common_test_cron_helper');

  public static function getInfo() {
    return array(
      'name' => 'Cron run',
      'description' => 'Test cron run.',
      'group' => 'System',
    );
  }

  /**
   * Test cron runs.
   */
  function testCronRun() {
    global $base_url;

    // Run cron anonymously without any cron key.
    $this->drupalGet('cron');
    $this->assertResponse(404);

    // Run cron anonymously with a random cron key.
    $key = $this->randomName(16);
    $this->drupalGet('cron/' . $key);
    $this->assertResponse(403);

    // Run cron anonymously with the valid cron key.
    $key = config('system.cron')->get('key');
    $this->drupalGet('cron/' . $key);
    $this->assertResponse(204);
  }

  /**
   * Ensure that the automatic cron run feature is working.
   *
   * In these tests we do not use REQUEST_TIME to track start time, because we
   * need the exact time when cron is triggered.
   */
  function testAutomaticCron() {
    // Ensure cron does not run when the cron threshold is enabled and was
    // not passed.
    $cron_last = time();
    $cron_safe_threshold = 100;
    variable_set('cron_last', $cron_last);
    config('system.cron')
      ->set('threshold.autorun', $cron_safe_threshold)
      ->save();
    $this->drupalGet('');
    $this->assertTrue($cron_last == variable_get('cron_last', NULL), 'Cron does not run when the cron threshold is not passed.');

    // Test if cron runs when the cron threshold was passed.
    $cron_last = time() - 200;
    variable_set('cron_last', $cron_last);
    $this->drupalGet('');
    sleep(1);
    $this->assertTrue($cron_last < variable_get('cron_last', NULL), 'Cron runs when the cron threshold is passed.');

    // Disable the cron threshold through the interface.
    $admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin_user);
    $this->drupalPost('admin/config/system/cron', array('cron_safe_threshold' => 0), t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
    $this->drupalLogout();

    // Test if cron does not run when the cron threshold is disabled.
    $cron_last = time() - 200;
    variable_set('cron_last', $cron_last);
    $this->drupalGet('');
    $this->assertTrue($cron_last == variable_get('cron_last', NULL), 'Cron does not run when the cron threshold is disabled.');
  }

  /**
   * Make sure exceptions thrown on hook_cron() don't affect other modules.
   */
  function testCronExceptions() {
    variable_del('common_test_cron');
    // The common_test module throws an exception. If it isn't caught, the tests
    // won't finish successfully.
    // The common_test_cron_helper module sets the 'common_test_cron' variable.
    $this->cronRun();
    $result = variable_get('common_test_cron');
    $this->assertEqual($result, 'success', 'Cron correctly handles exceptions thrown during hook_cron() invocations.');
  }
}
