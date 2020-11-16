<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests user-account links.
 *
 * @group user
 */
class UserAccountLinksTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['menu_ui', 'block', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_menu_block:account');
    // Make test-page default.
    $this->config('system.site')->set('page.front', '/test-page')->save();
  }

  /**
   * Tests the secondary menu.
   */
  public function testSecondaryMenu() {
    // Create a regular user.
    $user = $this->drupalCreateUser([]);

    // Log in and get the homepage.
    $this->drupalLogin($user);
    $this->drupalGet('<front>');

    // For a logged-in user, expect the secondary menu to have links for "My
    // account" and "Log out".
    $link = $this->xpath('//ul[@class=:menu_class]/li/a[contains(@href, :href) and text()=:text]', [
      ':menu_class' => 'menu',
      ':href' => 'user',
      ':text' => 'My account',
    ]);
    $this->assertCount(1, $link, 'My account link is in secondary menu.');

    $link = $this->xpath('//ul[@class=:menu_class]/li/a[contains(@href, :href) and text()=:text]', [
      ':menu_class' => 'menu',
      ':href' => 'user/logout',
      ':text' => 'Log out',
    ]);
    $this->assertCount(1, $link, 'Log out link is in secondary menu.');

    // Log out and get the homepage.
    $this->drupalLogout();
    $this->drupalGet('<front>');

    // For a logged-out user, expect the secondary menu to have a "Log in" link.
    $link = $this->xpath('//ul[@class=:menu_class]/li/a[contains(@href, :href) and text()=:text]', [
      ':menu_class' => 'menu',
      ':href' => 'user/login',
      ':text' => 'Log in',
    ]);
    $this->assertCount(1, $link, 'Log in link is in secondary menu.');
  }

  /**
   * Tests disabling the 'My account' link.
   */
  public function testDisabledAccountLink() {
    // Create an admin user and log in.
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer menu',
    ]));

    // Verify that the 'My account' link exists before we check for its
    // disappearance.
    $link = $this->xpath('//ul[@class=:menu_class]/li/a[contains(@href, :href) and text()=:text]', [
      ':menu_class' => 'menu',
      ':href' => 'user',
      ':text' => 'My account',
    ]);
    $this->assertCount(1, $link, 'My account link is in the secondary menu.');

    // Verify that the 'My account' link is enabled. Do not assume the value of
    // auto-increment is 1. Use XPath to obtain input element id and name using
    // the consistent label text.
    $this->drupalGet('admin/structure/menu/manage/account');
    $label = $this->xpath('//label[contains(.,:text)]/@for', [':text' => 'Enable My account menu link']);
    $this->assertSession()->checkboxChecked($label[0]->getText());

    // Disable the 'My account' link.
    $edit['links[menu_plugin_id:user.page][enabled]'] = FALSE;
    $this->drupalPostForm('admin/structure/menu/manage/account', $edit, 'Save');

    // Get the homepage.
    $this->drupalGet('<front>');

    // Verify that the 'My account' link does not appear when disabled.
    $link = $this->xpath('//ul[@class=:menu_class]/li/a[contains(@href, :href) and text()=:text]', [
      ':menu_class' => 'menu',
      ':href' => 'user',
      ':text' => 'My account',
    ]);
    $this->assertCount(0, $link, 'My account link is not in the secondary menu.');
  }

  /**
   * Tests page title is set correctly on user account tabs.
   */
  public function testAccountPageTitles() {
    // Default page titles are suffixed with the site name - Drupal.
    $title_suffix = ' | Drupal';

    $this->drupalGet('user');
    $this->assertSession()->titleEquals('Log in' . $title_suffix);

    $this->drupalGet('user/login');
    $this->assertSession()->titleEquals('Log in' . $title_suffix);

    $this->drupalGet('user/register');
    $this->assertSession()->titleEquals('Create new account' . $title_suffix);

    $this->drupalGet('user/password');
    $this->assertSession()->titleEquals('Reset your password' . $title_suffix);

    // Check the page title for registered users is "My Account" in menus.
    $this->drupalLogin($this->drupalCreateUser());
    // After login, the client is redirected to /user.
    $this->assertSession()->linkExists('My account', 0, "Page title of /user is 'My Account' in menus for registered users");
    $this->assertSession()->linkByHrefExists(\Drupal::urlGenerator()->generate('user.page'), 0);
  }

  /**
   * Ensures that logout url redirects an anonymous user to the front page.
   */
  public function testAnonymousLogout() {
    $this->drupalGet('user/logout');
    $this->assertSession()->addressEquals('/');
    $this->assertSession()->statusCodeEquals(200);
  }

}
