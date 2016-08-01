<?php

/**
 * @file
 * Test fixture for \Drupal\rest\Tests\Update\RestExportAuthUpdateTest.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();
$config = $connection;

// Set the schema version.
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'rest',
    'value' => 'i:8000;',
  ])
  ->fields([
    'collection' => 'system.schema',
    'name' => 'serialization',
    'value' => 'i:8000;',
  ])
  ->fields([
    'collection' => 'system.schema',
    'name' => 'basic_auth',
    'value' => 'i:8000;',
  ])
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['rest'] = 0;
$extensions['module']['serialization'] = 0;
$extensions['module']['basic_auth'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

$config = [
  'link_domain' => '~',
];
$data = $connection->insert('config')
  ->fields([
    'name' => 'rest.settings',
    'data' => serialize($config),
    'collection' => '',
  ])
  ->execute();

$connection->insert('config')
  ->fields([
    'name' => 'views.view.rest_export_with_authorization',
  ])
  ->execute();

$connection->merge('config')
  ->condition('name', 'views.view.rest_export_with_authorization')
  ->condition('collection', '')
  ->fields([
    'data' => serialize(Yaml::decode(file_get_contents('core/modules/views/tests/modules/views_test_config/test_views/views.view.rest_export_with_authorization.yml'))),
  ])
  ->execute();
