<?php

/**
 * @file
 * Contains \Drupal\plugin_test\Plugin\plugin_test\custom_annotation\Example1.
 */

namespace Drupal\plugin_test\Plugin\plugin_test\custom_annotation;

use Drupal\plugin_test\Plugin\Annotation\PluginExample;

/**
 * Provides a test plugin with a custom annotation.
 *
 * @PluginExample(
 *   id = "example_1",
 *   custom = "John"
 * )
 */
class Example1 {}
