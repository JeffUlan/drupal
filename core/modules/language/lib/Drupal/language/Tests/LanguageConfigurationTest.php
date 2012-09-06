<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageConfigurationTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for language configuration's effect on negotiation setup.
 */
class LanguageConfigurationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  public static function getInfo() {
    return array(
      'name' => 'Language negotiation autoconfiguration',
      'description' => 'Adds and configures languages to check negotiation changes.',
      'group' => 'Language',
    );
  }

  /**
   * Functional tests for adding, editing and deleting languages.
   */
  function testLanguageConfiguration() {
    global $base_url;

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    // Check if the Default English language has no path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertFieldByXPath('//input[@name="prefix[en]"]', '', 'Default English has no path prefix.');

    // Add predefined language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, 'Add language');
    $this->assertText('French');
    $this->assertEqual($this->getUrl(), url('admin/config/regional/language', array('absolute' => TRUE)), 'Correct page redirection.');

    // Check if the Default English language has no path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertFieldByXPath('//input[@name="prefix[en]"]', '', 'Default English has no path prefix.');
    // Check if French has a path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertFieldByXPath('//input[@name="prefix[fr]"]', 'fr', 'French has a path prefix.');

    // Check if we can change the default language.
    $this->drupalGet('admin/config/regional/language');
    $this->assertFieldChecked('edit-site-default-en', 'English is the default language.');
    // Change the default language.
    $edit = array(
      'site_default' => 'fr',
    );
    $this->drupalPost(NULL, $edit, t('Save configuration'));
    $this->assertNoFieldChecked('edit-site-default-en', 'Default language updated.');
    $this->assertEqual($this->getUrl(), url('admin/config/regional/language', array('absolute' => TRUE)), 'Correct page redirection.');

    // Check if a valid language prefix is added afrer changing the default
    // language.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertFieldByXPath('//input[@name="prefix[en]"]', 'en', 'A valid path prefix has been added to the previous default language.');
    // Check if French still has a path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertFieldByXPath('//input[@name="prefix[fr]"]', 'fr', 'French still has a path prefix.');

    // Check that prefix can be changed.
    $edit = array(
      'prefix[fr]' => 'french',
    );
    $this->drupalPost(NULL, $edit, t('Save configuration'));
    $this->assertFieldByXPath('//input[@name="prefix[fr]"]', 'french', 'French path prefix has changed.');

    // Check that prefix of non default language cannot be changed to
    // empty string.
    $edit = array(
      'prefix[en]' => '',
    );
    $this->drupalPost(NULL, $edit, t('Save configuration'));
    $this->assertText(t('The prefix may only be left blank for the default language.'), 'English prefix cannot be changed to empty string.');

    //  Check that prefix cannot be changed to contain a slash.
    $edit = array(
      'prefix[en]' => 'foo/bar',
    );
    $this->drupalPost(NULL, $edit, t('Save configuration'));
    $this->assertText(t('The prefix may not contain a slash.'), 'English prefix cannot be changed to contain a slash.');
  }
}
