<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageBrowserDetectionUnitTest.
 */

namespace Drupal\language\Tests;

use Drupal\Component\Utility\UserAgent;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests browser language detection.
 *
 * @group language
 */
class LanguageBrowserDetectionUnitTest extends WebTestBase {

  public static $modules = array('language');

  /**
   * Tests for adding, editing and deleting mappings between browser language
   * codes and Drupal language codes.
   */
  function testUIBrowserLanguageMappings() {
    // User to manage languages.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    // Check that the configure link exists.
    $this->drupalGet('admin/config/regional/language/detection');
    $this->assertLinkByHref('admin/config/regional/language/detection/browser');

    // Check that defaults are loaded from language.mappings.yml.
    $this->drupalGet('admin/config/regional/language/detection/browser');
    $this->assertField('edit-mappings-zh-cn-browser-langcode', 'zh-cn', 'Chinese browser language code found.');
    $this->assertField('edit-mappings-zh-cn-drupal-langcode', 'zh-hans-cn', 'Chinese Drupal language code found.');

    // Delete zh-cn language code.
    $browser_langcode = 'zh-cn';
    $this->drupalGet('admin/config/regional/language/detection/browser/delete/' . $browser_langcode);
    $message = t('Are you sure you want to delete @browser_langcode?', array(
      '@browser_langcode' => $browser_langcode,
    ));
    $this->assertRaw($message);

    // Confirm the delete.
    $edit = array();
    $this->drupalPostForm('admin/config/regional/language/detection/browser/delete/' . $browser_langcode, $edit, t('Confirm'));

    // We need raw here because %browser will add HTML.
    $t_args = array(
      '%browser' => $browser_langcode,
    );
    $this->assertRaw(t('The mapping for the %browser browser language code has been deleted.', $t_args), 'The test browser language code has been deleted.');

    // Check we went back to the browser negotiation mapping overview.
    $this->assertUrl(\Drupal::url('language.negotiation_browser', [], ['absolute' => TRUE]));
    // Check that ch-zn no longer exists.
    $this->assertNoField('edit-mappings-zh-cn-browser-langcode', 'Chinese browser language code no longer exists.');

    // Add a new custom mapping.
    $edit = array(
      'new_mapping[browser_langcode]' => 'xx',
      'new_mapping[drupal_langcode]' => 'en',
    );
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, t('Save configuration'));
    $this->assertUrl(\Drupal::url('language.negotiation_browser', [], ['absolute' => TRUE]));
    $this->assertField('edit-mappings-xx-browser-langcode', 'xx', 'Browser language code found.');
    $this->assertField('edit-mappings-xx-drupal-langcode', 'en', 'Drupal language code found.');

    // Add the same custom mapping again.
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, t('Save configuration'));
    $this->assertText('Browser language codes must be unique.');

    // Change browser language code of our custom mapping to zh-sg.
    $edit = array(
      'mappings[xx][browser_langcode]' => 'zh-sg',
      'mappings[xx][drupal_langcode]' => 'en',
    );
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, t('Save configuration'));
    $this->assertText(t('Browser language codes must be unique.'));

    // Change Drupal language code of our custom mapping to zh-hans.
    $edit = array(
      'mappings[xx][browser_langcode]' => 'xx',
      'mappings[xx][drupal_langcode]' => 'zh-hans',
    );
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, t('Save configuration'));
    $this->assertUrl(\Drupal::url('language.negotiation_browser', [], ['absolute' => TRUE]));
    $this->assertField('edit-mappings-xx-browser-langcode', 'xx', 'Browser language code found.');
    $this->assertField('edit-mappings-xx-drupal-langcode', 'zh-hans', 'Drupal language code found.');
  }
}
