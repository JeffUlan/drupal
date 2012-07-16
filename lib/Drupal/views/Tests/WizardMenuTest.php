<?php

/**
 * @file
 * Definition of Drupal\views\Tests\WizardMenuTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests the ability of the views wizard to put views in a menu.
 */
class WizardMenuTest extends WizardTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Views UI wizard menu functionality',
      'description' => 'Test the ability of the views wizard to put views in a menu.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests the menu functionality.
   */
  function testMenus() {
    // Create a view with a page display and a menu link in the Main Menu.
    $view = array();
    $view['human_name'] = $this->randomName(16);
    $view['name'] = strtolower($this->randomName(16));
    $view['description'] = $this->randomName(16);
    $view['page[create]'] = 1;
    $view['page[title]'] = $this->randomName(16);
    $view['page[path]'] = $this->randomName(16);
    $view['page[link]'] = 1;
    $view['page[link_properties][menu_name]'] = 'main-menu';
    $view['page[link_properties][title]'] = $this->randomName(16);
    $this->drupalPost('admin/structure/views/add', $view, t('Save & exit'));

    // Make sure there is a link to the view from the front page (where we
    // expect the main menu to display).
    $this->drupalGet('');
    $this->assertLink($view['page[link_properties][title]']);
    $this->assertLinkByHref(url($view['page[path]']));

    // Make sure the link is associated with the main menu.
    $links = menu_load_links('main-menu');
    $found = FALSE;
    foreach ($links as $link) {
      if ($link['link_path'] == $view['page[path]']) {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found, t('Found a link to %path in the main menu', array('%path' => $view['page[path]'])));
  }
}

