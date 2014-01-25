<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageTestBase.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Test for dependency injected language object.
 */
abstract class LanguageTestBase extends DrupalUnitTestBase {

  public static $modules = array('system', 'language', 'language_test');
  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', array('variable'));
    $this->installConfig(array('language'));

    $this->state = $this->container->get('state');

    // Ensure we are building a new Language object for each test.
    $this->languageManager = $this->container->get('language_manager');
    $this->languageManager->reset();
  }

}
