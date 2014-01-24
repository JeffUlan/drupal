<?php

/**
 * @file
 * Definition of \Drupal\simpletest\Tests\BrokenSetUpTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests a test case that does not run parent::setUp() in its setUp() method.
 *
 * If a test case does not call parent::setUp(), running
 * \Drupal\simpletest\WebTestBase::tearDown() would destroy the main site's
 * database tables. Therefore, we ensure that tests which are not set up
 * properly are skipped.
 *
 * @see \Drupal\simpletest\WebTestBase
 */
class BrokenSetUpTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest');

  public static function getInfo() {
    return array(
      'name' => 'Broken SimpleTest method',
      'description' => 'Tests a test case that does not call parent::setUp().',
      'group' => 'SimpleTest'
    );
  }

  function setUp() {
    // If the test is being run from the main site, set up normally.
    if (!drupal_valid_test_ua()) {
      parent::setUp();
      // Create and log in user.
      $admin_user = $this->drupalCreateUser(array('administer unit tests'));
      $this->drupalLogin($admin_user);
    }
    // If the test is being run from within simpletest, set up the broken test.
    else {
      if (file_get_contents($this->originalFileDirectory . '/simpletest/trigger') === 'setup') {
        throw new \Exception('Broken setup');
      }
      $this->pass('The setUp() method has run.');
    }
  }

  function tearDown() {
    // If the test is being run from the main site, tear down normally.
    if (!drupal_valid_test_ua()) {
      unlink($this->originalFileDirectory . '/simpletest/trigger');
      parent::tearDown();
    }
    // If the test is being run from within simpletest, output a message.
    else {
      if (file_get_contents($this->originalFileDirectory . '/simpletest/trigger') === 'teardown') {
        throw new \Exception('Broken teardown');
      }
      $this->pass('The tearDown() method has run.');
    }
  }

  /**
   * Runs this test case from within the simpletest child site.
   */
  function testMethod() {
    // If the test is being run from the main site, run it again from the web
    // interface within the simpletest child site.
    if (!drupal_valid_test_ua()) {
      // Verify that a broken setUp() method is caught.
      file_put_contents($this->originalFileDirectory . '/simpletest/trigger', 'setup');
      $edit['Drupal\simpletest\Tests\BrokenSetUpTest'] = TRUE;
      $this->drupalPostForm('admin/config/development/testing', $edit, t('Run tests'));
      $this->assertRaw('Broken setup');
      $this->assertNoRaw('The setUp() method has run.');
      $this->assertNoRaw('Broken test');
      $this->assertNoRaw('The test method has run.');
      $this->assertNoRaw('Broken teardown');
      $this->assertNoRaw('The tearDown() method has run.');

      // Verify that a broken tearDown() method is caught.
      file_put_contents($this->originalFileDirectory . '/simpletest/trigger', 'teardown');
      $edit['Drupal\simpletest\Tests\BrokenSetUpTest'] = TRUE;
      $this->drupalPostForm('admin/config/development/testing', $edit, t('Run tests'));
      $this->assertNoRaw('Broken setup');
      $this->assertRaw('The setUp() method has run.');
      $this->assertNoRaw('Broken test');
      $this->assertRaw('The test method has run.');
      $this->assertRaw('Broken teardown');
      $this->assertNoRaw('The tearDown() method has run.');

      // Verify that a broken test method is caught.
      file_put_contents($this->originalFileDirectory . '/simpletest/trigger', 'test');
      $edit['Drupal\simpletest\Tests\BrokenSetUpTest'] = TRUE;
      $this->drupalPostForm('admin/config/development/testing', $edit, t('Run tests'));
      $this->assertNoRaw('Broken setup');
      $this->assertRaw('The setUp() method has run.');
      $this->assertRaw('Broken test');
      $this->assertNoRaw('The test method has run.');
      $this->assertNoRaw('Broken teardown');
      $this->assertRaw('The tearDown() method has run.');
    }
    // If the test is being run from within simpletest, output a message.
    else {
      if (file_get_contents($this->originalFileDirectory . '/simpletest/trigger') === 'test') {
        throw new \Exception('Broken test');
      }
      $this->pass('The test method has run.');
    }
  }
}
