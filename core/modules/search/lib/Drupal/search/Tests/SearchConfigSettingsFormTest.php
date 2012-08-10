<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchConfigSettingsFormTest.
 */

namespace Drupal\search\Tests;

/**
 * Test config page.
 */
class SearchConfigSettingsFormTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'search_extra_type');

  public $search_user;
  public $search_node;

  public static function getInfo() {
    return array(
      'name' => 'Config settings form',
      'description' => 'Verify the search config settings form.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp();

    // Login as a user that can create and search content.
    $this->search_user = $this->drupalCreateUser(array('search content', 'administer search', 'administer nodes', 'bypass node access', 'access user profiles', 'administer users', 'administer blocks'));
    $this->drupalLogin($this->search_user);

    // Add a single piece of content and index it.
    $node = $this->drupalCreateNode();
    $this->search_node = $node;
    // Link the node to itself to test that it's only indexed once. The content
    // also needs the word "pizza" so we can use it as the search keyword.
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $body_key = "body[$langcode][0][value]";
    $edit[$body_key] = l($node->label(), 'node/' . $node->nid) . ' pizza sandwich';
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));

    node_update_index();
    search_update_totals();

    // Enable the search block.
    $edit = array();
    $edit['blocks[search_form][region]'] = 'content';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
  }

  /**
   * Verify the search settings form.
   */
  function testSearchSettingsPage() {

    // Test that the settings form displays the correct count of items left to index.
    $this->drupalGet('admin/config/search/settings');
    $this->assertText(t('There are @count items left to index.', array('@count' => 0)));

    // Test the re-index button.
    $this->drupalPost('admin/config/search/settings', array(), t('Re-index site'));
    $this->assertText(t('Are you sure you want to re-index the site'));
    $this->drupalPost('admin/config/search/settings/reindex', array(), t('Re-index site'));
    $this->assertText(t('The index will be rebuilt'));
    $this->drupalGet('admin/config/search/settings');
    $this->assertText(t('There is 1 item left to index.'));

    // Test that the form saves with the default values.
    $this->drupalPost('admin/config/search/settings', array(), t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'Form saves with the default values.');

    // Test that the form does not save with an invalid word length.
    $edit = array(
      'minimum_word_size' => $this->randomName(3),
    );
    $this->drupalPost('admin/config/search/settings', $edit, t('Save configuration'));
    $this->assertNoText(t('The configuration options have been saved.'), 'Form does not save with an invalid word length.');
  }

  /**
   * Verify that you can disable individual search modules.
   */
  function testSearchModuleDisabling() {
    // Array of search modules to test: 'path' is the search path, 'title' is
    // the tab title, 'keys' are the keywords to search for, and 'text' is
    // the text to assert is on the results page.
    $module_info = array(
      'node' => array(
        'path' => 'node',
        'title' => 'Content',
        'keys' => 'pizza',
        'text' => $this->search_node->label(),
      ),
      'user' => array(
        'path' => 'user',
        'title' => 'User',
        'keys' => $this->search_user->name,
        'text' => $this->search_user->mail,
      ),
      'search_extra_type' => array(
        'path' => 'dummy_path',
        'title' => 'Dummy search type',
        'keys' => 'foo',
        'text' => 'Dummy search snippet to display',
      ),
    );
    $modules = array_keys($module_info);

    // Test each module if it's enabled as the only search module.
    foreach ($modules as $module) {
      // Enable the one module and disable other ones.
      $info = $module_info[$module];
      $edit = array();
      foreach ($modules as $other) {
        $edit['active_modules[' . $other . ']'] = (($other == $module) ? $module : FALSE);
      }
      $edit['default_module'] = $module;
      $this->drupalPost('admin/config/search/settings', $edit, t('Save configuration'));

      // Run a search from the correct search URL.
      $this->drupalGet('search/' . $info['path'] . '/' . $info['keys']);
      $this->assertNoText('no results', $info['title'] . ' search found results');
      $this->assertText($info['text'], 'Correct search text found');

      // Verify that other module search tab titles are not visible.
      foreach ($modules as $other) {
        if ($other != $module) {
          $title = $module_info[$other]['title'];
          $this->assertNoText($title, $title . ' search tab is not shown');
        }
      }

      // Run a search from the search block on the node page. Verify you get
      // to this module's search results page.
      $terms = array('search_block_form' => $info['keys']);
      $this->drupalPost('node', $terms, t('Search'));
      $this->assertEqual(
        $this->getURL(),
        url('search/' . $info['path'] . '/' . $info['keys'], array('absolute' => TRUE)),
        'Block redirected to right search page');

      // Try an invalid search path. Should redirect to our active module.
      $this->drupalGet('search/not_a_module_path');
      $this->assertEqual(
        $this->getURL(),
        url('search/' . $info['path'], array('absolute' => TRUE)),
        'Invalid search path redirected to default search page');
    }

    // Test with all search modules enabled. When you go to the search
    // page or run search, all modules should be shown.
    $edit = array();
    foreach ($modules as $module) {
      $edit['active_modules[' . $module . ']'] = $module;
    }
    $edit['default_module'] = 'node';

    $this->drupalPost('admin/config/search/settings', $edit, t('Save configuration'));

    foreach (array('search/node/pizza', 'search/node') as $path) {
      $this->drupalGet($path);
      foreach ($modules as $module) {
        $title = $module_info[$module]['title'];
        $this->assertText($title, $title . ' search tab is shown');
      }
    }
  }
}
