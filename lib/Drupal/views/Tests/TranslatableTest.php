<?php

/**
 * @file
 * Definition of Drupal\views\Tests\TranslatableTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\View;

/**
 * Tests Views pluggable translations.
 */
class TranslatableTest extends ViewsSqlTest {
  /**
   * Stores the strings, which are tested by this test.
   *
   * @var array
   */
  protected $strings;

  public static function getInfo() {
    return array(
      'name' => 'Views Translatable Test',
      'description' => 'Tests the pluggable translations',
      'group' => 'Views',
    );
  }

  protected function setUp() {
    parent::setUp();

    config('views.settings')->set('localization_plugin', 'test_localization')->save();
    // Reset the plugin data.
    views_fetch_plugin_data(NULL, NULL, TRUE);
    $this->strings = array(
      'Master1',
      'Apply1',
      'Sort By1',
      'Asc1',
      'Desc1',
      'more1',
      'Reset1',
      'Offset1',
      'Master1',
      'title1',
      'Items per page1',
      'fieldlabel1',
      'filterlabel1'
    );
  }

  /**
   * Tests the unpack translation funtionality.
   */
  public function testUnpackTranslatable() {
    $view = $this->view_unpack_translatable();
    $view->init_localization();

    $this->assertEqual('Drupal\views_test\Plugin\views\localization\LocalizationTest', get_class($view->localization_plugin), 'Make sure that init_localization initializes the right translation plugin');

    $view->export_locale_strings();

    $expected_strings = $this->strings;
    $result_strings = $view->localization_plugin->get_export_strings();
    $this->assertEqual(sort($expected_strings), sort($result_strings), 'Make sure that the localization plugin got every translatable string.');
  }

  public function testUi() {
    // Make sure that the string is not translated in the UI.
    $view = $this->view_unpack_translatable();
    $view->save();
    views_invalidate_cache();

    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    $this->drupalGet("admin/structure/views/view/$view->name/edit");
    $this->assertNoText('-translated', 'Make sure that no strings get translated in the UI.');
  }

  /**
   * Make sure that the translations get into the loaded view.
   */
  public function testTranslation() {
    $view = $this->view_unpack_translatable();
    $view->set_display('default');
    $this->executeView($view);

    $expected_strings = array();
    foreach ($this->strings as $string) {
      $expected_strings[] = $string . '-translated';
    }

    sort($expected_strings);
    sort($view->localization_plugin->translated_strings);

    // @todo The plugin::unpack_options() method is missing some keys of the
    //   display, but calls the translate method two times per item.
    //$this->assertEqual($expected_strings, $view->localization_plugin->translated_strings, 'Make sure that every string got loaded translated');
  }

  /**
   * Make sure that the different things have the right translation keys.
   */
  public function testTranslationKey() {
    $view = $this->view_unpack_translatable();
    $view->editing = TRUE;
    $view->init_display();

    // Don't run translation. We just want to get the right keys.

    foreach ($view->display as $display_id => $display) {
      $translatables = array();
      $display->handler->unpack_translatables($translatables);

      $this->string_keys = array(
        'Master1' => array('title'),
        'Apply1' => array('exposed_form', 'submit_button'),
        'Sort By1' => array('exposed_form', 'exposed_sorts_label'),
        'Asc1' => array('exposed_form', 'sort_asc_label'),
        'Desc1' => array('exposed_form', 'sort_desc_label'),
        'more1' => array('use_more_text'),
        'Reset1' => array('exposed_form', 'reset_button_label'),
        'Offset1' => array('pager', 'expose', 'offset_label'),
        'Master1' => array('title'),
        'title1' => array('title'),
        'Tag first1' => array('pager', 'tags', 'first'),
        'Tag prev1' => array('pager', 'tags', 'previous'),
        'Tag next1' => array('pager', 'tags', 'next'),
        'Tag last1' => array('pager', 'tags', 'last'),
        'Items per page1' => array('pager', 'expose', 'items_per_page_label'),
        'fieldlabel1' => array('field', 'node', 'nid', 'label'),
        'filterlabel1' => array('filter', 'node', 'nid', 'expose', 'label'),
        '- All -' => array('pager', 'expose', 'items_per_page_options_all_label'),
      );
      foreach ($translatables as $translatable) {
        $this->assertEqual($translatable['keys'], $this->string_keys[$translatable['value']]);
      }
    }
  }

  public function view_unpack_translatable() {
    $view = new View();
    $view->name = 'view_unpack_translatable';
    $view->description = '';
    $view->tag = '';
    $view->base_table = 'node';
    $view->api_version = '3.0-alpha1';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master1', 'default');
    $handler->display->display_options['title'] = 'title1';
    $handler->display->display_options['use_more_text'] = 'more1';
    $handler->display->display_options['access']['type'] = 'none';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['query']['type'] = 'views_query';
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['exposed_form']['options']['submit_button'] = 'Apply1';
    $handler->display->display_options['exposed_form']['options']['reset_button'] = TRUE;
    $handler->display->display_options['exposed_form']['options']['reset_button_label'] = 'Reset1';
    $handler->display->display_options['exposed_form']['options']['exposed_sorts_label'] = 'Sort By1';
    $handler->display->display_options['exposed_form']['options']['sort_asc_label'] = 'Asc1';
    $handler->display->display_options['exposed_form']['options']['sort_desc_label'] = 'Desc1';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['pager']['options']['items_per_page'] = '10';
    $handler->display->display_options['pager']['options']['offset'] = '0';
    $handler->display->display_options['pager']['options']['id'] = '0';
    $handler->display->display_options['pager']['options']['quantity'] = '9';
    $handler->display->display_options['pager']['options']['tags']['first'] = 'Tag first1';
    $handler->display->display_options['pager']['options']['tags']['previous'] = 'Tag prev1';
    $handler->display->display_options['pager']['options']['tags']['next'] = 'Tag next1';
    $handler->display->display_options['pager']['options']['tags']['last'] = 'Tag last1';
    $handler->display->display_options['pager']['options']['expose']['items_per_page'] = TRUE;
    $handler->display->display_options['pager']['options']['expose']['items_per_page_label'] = 'Items per page1';
    $handler->display->display_options['pager']['options']['expose']['offset'] = TRUE;
    $handler->display->display_options['pager']['options']['expose']['offset_label'] = 'Offset1';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'fields';
    /* Field: Content: Nid */
    $handler->display->display_options['fields']['nid']['id'] = 'nid';
    $handler->display->display_options['fields']['nid']['table'] = 'node';
    $handler->display->display_options['fields']['nid']['field'] = 'nid';
    $handler->display->display_options['fields']['nid']['label'] = 'fieldlabel1';
    $handler->display->display_options['fields']['nid']['alter']['alter_text'] = 0;
    $handler->display->display_options['fields']['nid']['alter']['make_link'] = 0;
    $handler->display->display_options['fields']['nid']['alter']['trim'] = 0;
    $handler->display->display_options['fields']['nid']['alter']['word_boundary'] = 1;
    $handler->display->display_options['fields']['nid']['alter']['ellipsis'] = 1;
    $handler->display->display_options['fields']['nid']['alter']['strip_tags'] = 0;
    $handler->display->display_options['fields']['nid']['alter']['html'] = 0;
    $handler->display->display_options['fields']['nid']['hide_empty'] = 0;
    $handler->display->display_options['fields']['nid']['empty_zero'] = 0;
    $handler->display->display_options['fields']['nid']['link_to_node'] = 0;
    /* Filter: Content: Nid */
    $handler->display->display_options['filters']['nid']['id'] = 'nid';
    $handler->display->display_options['filters']['nid']['table'] = 'node';
    $handler->display->display_options['filters']['nid']['field'] = 'nid';
    $handler->display->display_options['filters']['nid']['exposed'] = TRUE;
    $handler->display->display_options['filters']['nid']['expose']['operator_id'] = 'nid_op';
    $handler->display->display_options['filters']['nid']['expose']['label'] = 'filterlabel1';
    $handler->display->display_options['filters']['nid']['expose']['identifier'] = 'nid';
    $handler->display->display_options['filters']['nid']['expose']['multiple'] = 1;
    $handler->display->display_options['filters']['nid']['expose']['reduce'] = 0;

    return $view;
  }
}
