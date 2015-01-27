<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Batch\PageTest.
 */

namespace Drupal\system\Tests\Batch;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the content of the progress page.
 *
 * @group Batch
 */
class PageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('batch_test');

  /**
   * Tests that the batch API progress page uses the correct theme.
   */
  function testBatchProgressPageTheme() {
    // Make sure that the page which starts the batch (an administrative page)
    // is using a different theme than would normally be used by the batch API.
    $this->container->get('theme_handler')->install(array('seven', 'bartik'));
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->set('admin', 'seven')
      ->save();

    // Log in as an administrator who can see the administrative theme.
    $admin_user = $this->drupalCreateUser(array('view the administration theme'));
    $this->drupalLogin($admin_user);
    // Visit an administrative page that runs a test batch, and check that the
    // theme that was used during batch execution (which the batch callback
    // function saved as a variable) matches the theme used on the
    // administrative page.
    $this->drupalGet('admin/batch-test/test-theme');
    // The stack should contain the name of the theme used on the progress
    // page.
    $this->assertEqual(batch_test_stack(), array('seven'), 'A progressive batch correctly uses the theme of the page that started the batch.');
  }

  /**
   * Tests that the batch API progress page shows the title correctly.
   */
  function testBatchProgressPageTitle() {
    // Visit an administrative page that runs a test batch, and check that the
    // title shown during batch execution (which the batch callback function
    // saved as a variable) matches the theme used on the administrative page.
    $this->drupalGet('batch-test/test-title');
    // The stack should contain the title shown on the progress page.
    $this->assertEqual(batch_test_stack(), ['Batch Test'], 'The batch title is shown on the batch page.');
    $this->assertText('Redirection successful.', 'Redirection after batch execution is correct.');
  }

}
