<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\ContentFieldTest.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the content_field_test table.
 */
class ContentFieldTest extends Drupal6DumpBase {

  public function load() {
    $this->createTable("content_field_test", array(
      'primary key' => array(
        'vid',
      ),
      'fields' => array(
        'vid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'field_test_value' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'field_test_format' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("content_field_test")->fields(array(
      'vid',
      'nid',
      'field_test_value',
      'field_test_format',
    ))
    ->values(array(
      'vid' => '1',
      'nid' => '1',
      'field_test_value' => 'This is a shared text field',
      'field_test_format' => '1',
    ))->values(array(
      'vid' => '2',
      'nid' => '1',
      'field_test_value' => 'This is a shared text field',
      'field_test_format' => '1',
    ))->values(array(
      'vid' => '3',
      'nid' => '2',
      'field_test_value' => NULL,
      'field_test_format' => NULL,
    ))->values(array(
      'vid' => '5',
      'nid' => '2',
      'field_test_value' => NULL,
      'field_test_format' => NULL,
    ))->execute();
  }

}
