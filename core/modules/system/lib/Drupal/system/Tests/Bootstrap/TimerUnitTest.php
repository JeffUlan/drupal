<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bootstrap\TimerUnitTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests timer_read().
 */
class TimerUnitTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Timer test',
      'description' => 'Test that timer_read() works both when a timer is running and when a timer is stopped.',
      'group' => 'Bootstrap',
    );
  }

  /**
   * Tests timer_read() time accumulation accuracy across multiple restarts.
   */
  function testTimer() {
    timer_start('test');
    sleep(1);
    $this->assertTrue(timer_read('test') >= 1000, 'Timer measured 1 second of sleeping while running.');
    sleep(1);
    timer_stop('test');
    $this->assertTrue(timer_read('test') >= 2000, 'Timer measured 2 seconds of sleeping after being stopped.');
    timer_start('test');
    sleep(1);
    $this->assertTrue(timer_read('test') >= 3000, 'Timer measured 3 seconds of sleeping after being restarted.');
    sleep(1);
    $timer = timer_stop('test');
    $this->assertTrue(timer_read('test') >= 4000, 'Timer measured 4 seconds of sleeping after being stopped for a second time.');
    $this->assertEqual($timer['count'], 2, 'Timer counted 2 instances of being started.');
  }
}
