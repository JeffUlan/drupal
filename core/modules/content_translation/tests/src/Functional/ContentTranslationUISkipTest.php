<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the content translation UI check skip.
 *
 * @group content_translation
 */
class ContentTranslationUISkipTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['content_translation_test', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the content_translation_ui_skip key functionality.
   */
  public function testUICheckSkip() {
    $admin_user = $this->drupalCreateUser([
      'translate any entity',
      'administer content translation',
      'administer languages',
    ]);
    $this->drupalLogin($admin_user);
    // Visit the content translation.
    $this->drupalGet('admin/config/regional/content-language');

    // Check the message regarding UI integration.
    $this->assertSession()->pageTextContains('Test entity - Translatable skip UI check');
    $this->assertSession()->pageTextContains('Test entity - Translatable check UI (Translation is not supported)');
  }

}
