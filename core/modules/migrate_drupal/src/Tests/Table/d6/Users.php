<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Users.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the users table.
 */
class Users extends DrupalDumpBase {

  public function load() {
    $this->createTable("users", array(
      'primary key' => array(
        'uid',
      ),
      'fields' => array(
        'uid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '60',
          'default' => '',
        ),
        'pass' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'mail' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '64',
          'default' => '',
        ),
        'mode' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'sort' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
          'default' => '0',
        ),
        'threshold' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
          'default' => '0',
        ),
        'theme' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'signature' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'signature_format' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'created' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'access' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'login' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'timezone' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '8',
        ),
        'language' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '12',
          'default' => '',
        ),
        'picture' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'init' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '64',
          'default' => '',
        ),
        'data' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'timezone_name' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '50',
          'default' => '',
        ),
        'pass_plain' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'expected_timezone' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '50',
        ),
        'timezone_id' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("users")->fields(array(
      'uid',
      'name',
      'pass',
      'mail',
      'mode',
      'sort',
      'threshold',
      'theme',
      'signature',
      'signature_format',
      'created',
      'access',
      'login',
      'status',
      'timezone',
      'language',
      'picture',
      'init',
      'data',
      'timezone_name',
      'pass_plain',
      'expected_timezone',
      'timezone_id',
    ))
    ->values(array(
      'uid' => '2',
      'name' => 'john.doe',
      'pass' => '671cc45b3e2c6eb751d6a554dc5a5fe7',
      'mail' => 'john.doe@example.com',
      'mode' => '0',
      'sort' => '0',
      'threshold' => '0',
      'theme' => '',
      'signature' => 'John Doe | john.doe@example.com',
      'signature_format' => '1',
      'created' => '1391150052',
      'access' => '1391259672',
      'login' => '1391152253',
      'status' => '1',
      'timezone' => '3600',
      'language' => 'fr',
      'picture' => 'core/modules/simpletest/files/image-test.jpg',
      'init' => 'doe@example.com',
      'data' => 'a:2:{s:7:"contact";i:1;s:13:"form_build_id";s:48:"form-qu_DMjE-Vfg01arT5J4VbuBCkOgx_LeySJx4qrPOSuA";}',
      'timezone_name' => 'Europe/Berlin',
      'pass_plain' => 'john.doe_pass',
      'expected_timezone' => 'Europe/Berlin',
      'timezone_id' => '1',
    ))->values(array(
      'uid' => '8',
      'name' => 'joe.roe',
      'pass' => '93a70546e6c032c135499fed70cfe438',
      'mail' => 'joe.roe@example.com',
      'mode' => '0',
      'sort' => '0',
      'threshold' => '0',
      'theme' => '',
      'signature' => 'JR',
      'signature_format' => '2',
      'created' => '1391150053',
      'access' => '1391259673',
      'login' => '1391152254',
      'status' => '1',
      'timezone' => '7200',
      'language' => 'ro',
      'picture' => 'core/modules/simpletest/files/image-test.png',
      'init' => 'roe@example.com',
      'data' => 'a:2:{s:7:"contact";i:0;s:13:"form_build_id";s:48:"form-1TxjbL2_1dEHIxEu2Db6OvEsSN1x9ILH1VCgnvsO6LE";}',
      'timezone_name' => 'Europe/Helsinki',
      'pass_plain' => 'joe.roe_pass',
      'expected_timezone' => 'Europe/Helsinki',
      'timezone_id' => '0',
    ))->values(array(
      'uid' => '15',
      'name' => 'joe.bloggs',
      'pass' => '2ff23139aeb404274dc67cbee8c64fb0',
      'mail' => 'joe.bloggs@example.com',
      'mode' => '0',
      'sort' => '0',
      'threshold' => '0',
      'theme' => '',
      'signature' => 'bloggs',
      'signature_format' => '1',
      'created' => '1391150054',
      'access' => '1391259674',
      'login' => '1391152255',
      'status' => '1',
      'timezone' => '-28800',
      'language' => 'en',
      'picture' => '',
      'init' => 'bloggs@example.com',
      'data' => 'a:0:{}',
      'timezone_name' => 'America/Anchorage',
      'pass_plain' => 'joe.bloggs_pass',
      'expected_timezone' => NULL,
      'timezone_id' => '0',
    ))->values(array(
      'uid' => '16',
      'name' => 'sal.saraniti',
      'pass' => '77404657c8bcd8e9aa8f3147856efb4f',
      'mail' => 'sal.saraniti@example.com',
      'mode' => '0',
      'sort' => '0',
      'threshold' => '0',
      'theme' => '',
      'signature' => '',
      'signature_format' => '0',
      'created' => '1391151054',
      'access' => '1391259574',
      'login' => '1391162255',
      'status' => '1',
      'timezone' => '0',
      'language' => 'en',
      'picture' => '',
      'init' => 'sal.saraniti@example.com',
      'data' => 'a:0:{}',
      'timezone_name' => 'UTC',
      'pass_plain' => 'sal.saraniti',
      'expected_timezone' => NULL,
      'timezone_id' => '0',
    ))->values(array(
      'uid' => '17',
      'name' => 'terry.saraniti',
      'pass' => '8fb310d3ec746d720e0e8efefd0cce5c',
      'mail' => 'terry.saraniti@example.com',
      'mode' => '0',
      'sort' => '0',
      'threshold' => '0',
      'theme' => '',
      'signature' => '',
      'signature_format' => '0',
      'created' => '1390151054',
      'access' => '1390259574',
      'login' => '1390162255',
      'status' => '1',
      'timezone' => NULL,
      'language' => 'en',
      'picture' => '',
      'init' => 'terry.saraniti@example.com',
      'data' => 'a:0:{}',
      'timezone_name' => NULL,
      'pass_plain' => 'terry.saraniti',
      'expected_timezone' => NULL,
      'timezone_id' => '0',
    ))->execute();
  }

}
#4a42570fa3c819b0b84e9552d689e2e7
