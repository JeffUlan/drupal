<?php

namespace Drupal\syslog\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests that syslog settings are properly updated during database updates.
 *
 * @group syslog
 */
class SyslogUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz',
    ];
  }

  /**
   * Tests that syslog.settings.facility has been converted from string to int.
   *
   * @see syslog_update_8400()
   */
  public function testSyslogSettingsFacilityDataType() {
    $config = $this->config('syslog.settings');
    $this->assertIdentical('128', $config->get('facility'));

    // Run updates.
    $this->runUpdates();

    $config = $this->config('syslog.settings');
    $this->assertIdentical(128, $config->get('facility'));
  }

}
