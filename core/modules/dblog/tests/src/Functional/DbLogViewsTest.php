<?php

namespace Drupal\Tests\dblog\Functional;

use Drupal\filter\Entity\FilterFormat;

/**
 * Generate events and verify dblog entries; verify user access to log reports
 * based on permissions. Using the dblog UI generated by a View.
 *
 * @see Drupal\dblog\Tests\DbLogTest
 *
 * @group dblog
 */
class DbLogViewsTest extends DbLogTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dblog', 'node', 'forum', 'help', 'block', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function getLogsEntriesTable() {
    return $this->xpath('.//table[contains(@class, "views-view-table")]/tbody/tr');
  }

  /**
   * {@inheritdoc}
   */
  protected function filterLogsEntries($type = NULL, $severity = NULL) {
    $query = [];
    if (isset($type)) {
      $query['type[]'] = $type;
    }
    if (isset($severity)) {
      $query['severity[]'] = $severity;
    }

    $this->drupalGet('admin/reports/dblog', ['query' => $query]);
  }

  /**
   * {@inheritdoc}
   */
  public function testDBLogAddAndClear() {
    // Is necesary to create the basic_html format because if absent after
    // delete the logs, a new log entry is created indicating that basic_html
    // format do not exists.
    $basic_html_format = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a> <em>',
          ],
        ],
      ],
    ]);
    $basic_html_format->save();

    parent::testDBLogAddAndClear();
  }

}
