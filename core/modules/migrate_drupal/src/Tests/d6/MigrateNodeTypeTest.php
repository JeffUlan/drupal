<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateNodeTypeTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade node types to node.type.*.yml.
 *
 * @todo https://www.drupal.org/node/2283977 adds an new config entity that
 *   allows customisations on the bundle level for base fields. Node status,
 *   promote and sticky are a case in point. We need to add the ability for
 *   migrate to create these fields and test it here.
 *
 * @group migrate_drupal
 */
class MigrateNodeTypeTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_node_type');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6NodeType.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests Drupal 6 node type to Drupal 8 migration.
   */
  public function testNodeType() {
    $migration = entity_load('migration', 'd6_node_type');
    // Test the test_page content type.
    $node_type_page = entity_load('node_type', 'test_page');
    $this->assertEqual($node_type_page->id(), 'test_page', 'Node type test_page loaded');
    $expected = array(
      'options' => array(
        'revision' => FALSE,
      ),
      'preview' => 1,
      'submitted' => TRUE,
    );

    // @todo: Fix due to https://www.drupal.org/node/2283977
    // $this->assertEqual($node_type_page->settings['node'], $expected, 'Node type test_page settings correct.');
    $this->assertEqual(array('test_page'), $migration->getIdMap()->lookupDestinationID(array('test_page')));

    // Test we have a body field.
    $instance = FieldInstanceConfig::loadByName('node', 'test_page', 'body');
    $this->assertEqual($instance->getLabel(), 'This is the body field label', 'Body field was found.');

    // Test the test_story content type.
    $node_type_story = entity_load('node_type', 'test_story');
    $this->assertEqual($node_type_story->id(), 'test_story', 'Node type test_story loaded');
    $expected = array(
      'options' => array(
        'revision' => FALSE,
      ),
      'preview' => 1,
      'submitted' => TRUE,
    );
    // @todo: Fix due to https://www.drupal.org/node/2283977
    // $this->assertEqual($node_type_story->settings['node'], $expected, 'Node type test_story settings correct.');
    $this->assertEqual(array('test_story'), $migration->getIdMap()->lookupDestinationID(array('test_story')));

    // Test we don't have a body field.
    $instance = FieldInstanceConfig::loadByName('node', 'test_story', 'body');
    $this->assertEqual($instance, NULL, 'No body field found');

    // Test the test_event content type.
    $node_type_event = entity_load('node_type', 'test_event');
    $this->assertEqual($node_type_event->id(), 'test_event', 'Node type test_event loaded');
    $expected = array(
      'options' => array(
        'revision' => TRUE,
      ),
      'preview' => 1,
      'submitted' => TRUE,
    );

    // @todo: Fix due to https://www.drupal.org/node/2283977
    // $this->assertEqual($node_type_event->settings['node'], $expected, 'Node type test_event settings correct.');
    $this->assertEqual(array('test_event'), $migration->getIdMap()->lookupDestinationID(array('test_event')));

    // Test we have a body field.
    $instance = FieldInstanceConfig::loadByName('node', 'test_event', 'body');
    $this->assertEqual($instance->getLabel(), 'Body', 'Body field was found.');
  }
}
