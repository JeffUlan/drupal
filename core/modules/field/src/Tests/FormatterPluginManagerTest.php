<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FormatterPluginManagerTest.
 */

namespace Drupal\field\Tests;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FormatterPluginManager;

/**
 * Tests the field formatter plugin manager.
 *
 * @group field
 */
class FormatterPluginManagerTest extends FieldUnitTestBase {

  /**
   * Tests that getInstance falls back on default if current is not applicable.
   *
   * @see \Drupal\field\Tests\WidgetPluginManagerTest::testNotApplicableFallback()
   */
  public function testNotApplicableFallback() {
    /** @var FormatterPluginManager $formatter_plugin_manager */
    $formatter_plugin_manager = \Drupal::service('plugin.manager.field.formatter');

    $base_field_definition = BaseFieldDefinition::create('test_field')
      // Set a name that will make isApplicable() return TRUE.
      ->setName('field_test_field');

    $formatter_options = array(
      'field_definition' => $base_field_definition,
      'view_mode' => 'default',
      'configuration' => array(
        'type' => 'field_test_applicable',
      ),
    );

    $instance = $formatter_plugin_manager->getInstance($formatter_options);
    $this->assertEqual($instance->getPluginId(), 'field_test_applicable');

    // Now set name to something that makes isApplicable() return FALSE.
    $base_field_definition->setName('deny_applicable');
    $instance = $formatter_plugin_manager->getInstance($formatter_options);

    // Instance should be default widget.
    $this->assertNotEqual($instance->getPluginId(), 'field_test_applicable');
    $this->assertEqual($instance->getPluginId(), 'field_test_default');
  }

}
