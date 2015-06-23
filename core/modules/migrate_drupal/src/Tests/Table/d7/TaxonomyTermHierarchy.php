<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\TaxonomyTermHierarchy.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the taxonomy_term_hierarchy table.
 */
class TaxonomyTermHierarchy extends DrupalDumpBase {

  public function load() {
    $this->createTable("taxonomy_term_hierarchy", array(
      'primary key' => array(
        'tid',
        'parent',
      ),
      'fields' => array(
        'tid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'parent' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("taxonomy_term_hierarchy")->fields(array(
      'tid',
      'parent',
    ))
    ->values(array(
      'tid' => '1',
      'parent' => '0',
    ))->values(array(
      'tid' => '2',
      'parent' => '0',
    ))->values(array(
      'tid' => '3',
      'parent' => '0',
    ))->values(array(
      'tid' => '5',
      'parent' => '0',
    ))->values(array(
      'tid' => '6',
      'parent' => '0',
    ))->values(array(
      'tid' => '4',
      'parent' => '3',
    ))->values(array(
      'tid' => '7',
      'parent' => '6',
    ))->values(array(
      'tid' => '8',
      'parent' => '6',
    ))->execute();
  }

}
#ea3220ca740097f9251ba194e9bd736d
