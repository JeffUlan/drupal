<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Blocks.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the blocks table.
 */
class Blocks extends Drupal6DumpBase {

  public function load() {
    $this->createTable("blocks", array(
      'primary key' => array(
        'bid',
      ),
      'fields' => array(
        'bid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'delta' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '0',
        ),
        'theme' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
        'region' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'custom' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
        'throttle' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
        'visibility' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
        'pages' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'cache' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '1',
        ),
      ),
    ));
    $this->database->insert("blocks")->fields(array(
      'bid',
      'module',
      'delta',
      'theme',
      'status',
      'weight',
      'region',
      'custom',
      'throttle',
      'visibility',
      'pages',
      'title',
      'cache',
    ))
    ->values(array(
      'bid' => '1',
      'module' => 'user',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '0',
      'region' => 'left',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1',
    ))->values(array(
      'bid' => '2',
      'module' => 'user',
      'delta' => '1',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '0',
      'region' => 'left',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1',
    ))->values(array(
      'bid' => '3',
      'module' => 'system',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '-5',
      'region' => 'footer',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => 'node/1',
      'title' => '',
      'cache' => '-1',
    ))->values(array(
      'bid' => '4',
      'module' => 'comment',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-9',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '1',
    ))->values(array(
      'bid' => '5',
      'module' => 'menu',
      'delta' => 'primary-links',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '-5',
      'region' => 'header',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1',
    ))->values(array(
      'bid' => '6',
      'module' => 'menu',
      'delta' => 'secondary-links',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-8',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1',
    ))->values(array(
      'bid' => '7',
      'module' => 'node',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-7',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1',
    ))->values(array(
      'bid' => '8',
      'module' => 'user',
      'delta' => '2',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '-9',
      'region' => 'right',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '1',
    ))->values(array(
      'bid' => '9',
      'module' => 'user',
      'delta' => '3',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '-6',
      'region' => 'right',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1',
    ))->values(array(
      'bid' => '10',
      'module' => 'block',
      'delta' => '1',
      'theme' => 'garland',
      'status' => '1',
      'weight' => '0',
      'region' => 'content',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '1',
      'pages' => '<front>',
      'title' => 'Static Block',
      'cache' => '-1',
    ))->values(array(
      'bid' => '11',
      'module' => 'block',
      'delta' => '2',
      'theme' => 'bluemarine',
      'status' => '1',
      'weight' => '-4',
      'region' => 'right',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '1',
      'pages' => 'node',
      'title' => 'Another Static Block',
      'cache' => '-1',
    ))->values(array(
      'bid' => '12',
      'module' => 'block',
      'delta' => '1',
      'theme' => 'test_theme',
      'status' => '1',
      'weight' => '-7',
      'region' => 'right',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1',
    ))->values(array(
      'bid' => '13',
      'module' => 'block',
      'delta' => '2',
      'theme' => 'test_theme',
      'status' => '1',
      'weight' => '-2',
      'region' => 'left',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1',
    ))->values(array(
      'bid' => '14',
      'module' => 'aggregator',
      'delta' => 'feed-5',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-2',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '1',
    ))->values(array(
      'bid' => '15',
      'module' => 'block',
      'delta' => '2',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '1',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '-1',
    ))->values(array(
      'bid' => '16',
      'module' => 'profile',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-5',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '5',
    ))->values(array(
      'bid' => '17',
      'module' => 'event',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-3',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '1',
    ))->values(array(
      'bid' => '18',
      'module' => 'event',
      'delta' => '1',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '0',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '1',
    ))->values(array(
      'bid' => '19',
      'module' => 'event',
      'delta' => 'event-upcoming-event',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-1',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '1',
    ))->values(array(
      'bid' => '20',
      'module' => 'book',
      'delta' => '0',
      'theme' => 'garland',
      'status' => '0',
      'weight' => '-4',
      'region' => '',
      'custom' => '0',
      'throttle' => '0',
      'visibility' => '0',
      'pages' => '',
      'title' => '',
      'cache' => '5',
    ))->execute();
  }

}
