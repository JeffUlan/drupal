<?php

/**
 * @file
 * Contains \Drupal\migrate_events_test\Plugin\migrate\source\DataSource.
 */

namespace Drupal\migrate_events_test\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Source returning a single hard-coded data row.
 *
 * @MigrateSource(
 *   id = "data"
 * )
 */
class DataSource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'data' => t('Data'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new \ArrayIterator([
      ['data' => 'dummy value'],
      ['data' => 'dummy value2'],
    ]);

  }

  public function __toString() {
    return 'Sample data for testing';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['data']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return 2;
  }

}
