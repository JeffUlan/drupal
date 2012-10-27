<?php

  /**
   * @file
   * Definition of Drupal\views\Tests\UI\DisplayExtenderUITest.
   */

namespace Drupal\views\Tests\UI;

/**
 * Tests the display extender UI.
 */
class DisplayExtenderUITest extends UITestBase {

  public static function getInfo() {
    return array(
      'name' => 'Display extender: UI',
      'description' => 'Tests the display extender UI.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests the display extender UI.
   */
  public function testDisplayExtenderUI() {
    config('views.settings')->set('display_extenders', array('display_extender_test'))->save();

    $view = $this->getView();
    $view_edit_url = "admin/structure/views/view/{$view->storage->get('name')}/edit";
    $display_option_url = 'admin/structure/views/nojs/display/test_view/default/test_extender_test_option';

    $this->drupalGet($view_edit_url);
    $this->assertLinkByHref($display_option_url, 0, 'Make sure the option defined by the test display extender appears in the UI.');

    $random_text = $this->randomName();
    $this->drupalPost($display_option_url, array('test_extender_test_option' => $random_text), t('Apply'));
    $this->assertLink($random_text);
    $this->drupalPost(NULL, array(), t('Save'));
    $view = views_get_view($view->storage->get('name'));
    $view->initDisplay();
    $this->assertEqual($view->display_handler->getOption('test_extender_test_option'), $random_text, 'Make sure that the display extender option got saved.');
  }

}
